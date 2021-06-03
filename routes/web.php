<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * WE don't have home link..
 */
Route::get('/', function () {
    return redirect()->to('/login');
});

/**
 * get page where user can sign in
 */
Route::get('/login', function () {
    return view('login');
});

/**
 * sign out user
 */
Route::get('/logout', function () {
    auth()->logout();
    return redirect()->to('/login');
});

/**
 * authorize user
 */
Route::post('/login', function () {
    $user = \App\Models\User::where('email', request()->email)
        ->where('password', request()->password)
        ->first();

    if (!$user)
        return view('login')->with([
            'error' => 'User with given email and password is not found!'
        ]);

    auth()->login($user);
    return redirect()->to('/upload');
});

/**
 * get page where user can upload the file
 */
Route::get('/upload', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    return view('upload');
});

/**
 * upload the file
 */
Route::post('/upload', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    $data = request()->file->getContent();
    file_put_contents('data.mdb', $data);

    \App\Jobs\ListTables::dispatch()->onQueue('default');
    sleep(5);

    return view('tables')->with([
        'tables' => \App\Models\Table::all(),
    ]);
});

/**
 * get page where the list of tables is displayed
 */
Route::get('/tables', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    return view('tables')->with([
        'tables' => \App\Models\Table::all(),
    ]);
});

use Illuminate\Support\Facades\Schema;

/**
 * fetch specific table data
 */
Route::get('/fetch', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    $table = \App\Models\Table::where(request()->only('id'))
        ->first();

    if (!$table)
        return view('fetch')->with([
            'error' => 'No data found for this table',
        ]);

    if (empty($table->filename)) {
        \App\Jobs\FetchTable::dispatch($table)->onQueue('default');
        sleep(5);
    }

    return view('fetch')->with([
        'rows' => DB::table($table->name)->get()->toArray(),
        'columns' => Schema::getColumnListing($table->name),
        'table' => $table,
    ]);
});

/**
 * remove all tables
 */
Route::get('/clear', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    foreach (\App\Models\Table::all() as $table) {
        if (!empty($table->filename))
            \Illuminate\Support\Facades\Schema::dropIfExists(str_replace('.csv', '', $table->filename));

        if (file_exists($table->filename))
            unlink($table->filename);
    }

    \App\Models\Table::truncate();

    return redirect()->to('/tables');
});

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * get data from database based on provided in request query
 * request: ['query_string' => String]
 *
 * if no query provided, return validation error..
 */
Route::get('/data', function () {
    if (empty(request()->query_string))
        return response('`query_string` is required!', 422);

    $hash = Hash::make(request()->query_string);
    $cachedResult = Redis::get('data-query-' . $hash);

    if ($cachedResult)
        return json_decode($cachedResult);

    $result = DB::select(
        DB::raw(urldecode(request()->query_string))
    );

    Redis::set('data-query-' . $hash, json_encode($result));

    return $result;
});

/**
 * get data from database based on provided in request query
 * request: ['email' => String, 'subject' => String, 'text' => String]
 *
 * if no query provided, return validation error..
 */
Route::post('/send-email', function () {
    if (empty(request()->email))
        return response('`Email` is required!', 422);

    if (empty(request()->subject))
        return response('`Subject` is required!', 422);

    if (empty(request()->text))
        return response('`Text` is required!', 422);

    \Illuminate\Support\Facades\Mail::to(request()->email)
        ->send(new \App\Mail\GeneralEmail(
            request()->text,
            request()->subject
        ));
});

/**
 * Used packages
 */
use GuzzleHttp\Client;

/**
 * get country data based on current location of user
 */
Route::get('/country', function () {
    $ip = $_SERVER['REMOTE_ADDR'];

    $country =  DB::table('countries')
        ->where('ip', $ip)
        ->first();

    if ($country)
        return $country->data;

    $IPClient = new Client([
        'base_uri' => 'http://api.ipstack.com/' . $ip . '?access_key=' . env('LOCATION_API'),
        'timeout'  => 2.0,
    ]);

    $data = $IPClient->request('GET');
    $data = json_decode($data->getBody()->getContents(), true);
    $countryName = $data['country_name'] ?? null;

    $countryClient = new Client([
        'base_uri' => 'https://restcountries.eu/rest/v2/name/' . $countryName,
        'timeout'  => 2.0,
    ]);

    $data = $countryClient->request('GET');
    $data = json_decode($data->getBody()->getContents(), true)[0];

    DB::table('countries')
        ->insert([
            'ip' => $ip,
            'data' => json_encode($data),
        ]);

    return $data;
});

use Mpdf\Mpdf;
use Illuminate\Support\Str;

/**
 * create pdf and keep it in cache 1 minute..
 */
Route::post('/pdf/create', function () {
    $html = request()->html;

    if (!$html)
        return response('Please provide correct HTML document!', 422);

    $pdfConverter = new Mpdf();
    $pdfConverter->WriteHTML($html);

    ob_start();

    $pdfConverter->Output();
    $data = ob_get_contents();

    ob_end_clean();

    $key = Str::random();

    //keep pdf only 1 minute
    Redis::set('pdf-' . $key, $data, 'EX', 60 * 3);

    return [
        'key' => $key
    ];
});

/**
 * get pdf if exists..
 */
Route::get('/pdf/get', function () {
    $key = request()->key;

    if (!$key)
        return response('Please provide correct link for fetching PDF document!', 422);

    $pdf = Redis::get('pdf-' . $key);

    if (!$pdf)
        return response(
            'PDF document is not found! It seems to be expired, please create new one..',
            422
        );

    header("Content-type:application/pdf");
    header("Content-Disposition:attachment;filename=file.pdf");

    return $pdf;
});

use App\Models\SearchIndexData;

/**
 * Update search data
 */
Route::post('/search/update', function () {
    $request = request()->all();

    if (empty($request['link']))
        return response('Please provide correct link to page!', 422);

    if (empty($request['title']))
        return response('Please provide correct link to title!', 422);

    if (empty($request['text']))
        return response('Please provide correct link to text!', 422);

    SearchIndexData::updateOrCreate(request()->only('link'),
        request()->only(['title', 'text'])
    );
});

/**
 * search in data
 */
Route::get('/search', function () {
    $search = request()->search;
    $lang = request()->lang;

    if (empty($search))
        return response('Please provide correct search phrase!', 422);

    $titleExactMatches = SearchIndexData::where('title', 'LIKE', $search)
        ->where(function ($q) use ($lang) {
            if (!empty($lang))
                $q->where('link', 'LIKE', '%' . $lang . '%');
        })
        ->get();

    $titleCloseMatches = SearchIndexData::where(function ($query) use ($search) {
        $query->where('title', 'LIKE', '%' . $search)
            ->orWhere('title', 'LIKE', $search . '%')
            ->orWhere('title', 'LIKE', '%' . $search . '%');
    })

        ->where(function ($q) use ($lang) {
            if (!empty($lang))
                $q->where('link', 'LIKE', '%' . $lang . '%');
        })
        ->get();

    $textExactMatches = SearchIndexData::where('text', 'LIKE', $search)
        ->where(function ($q) use ($lang) {
            if (!empty($lang))
                $q->where('link', 'LIKE', '%' . $lang . '%');
        })
        ->get();

    $textCloseMatches = SearchIndexData::where(function ($query) use ($search) {
        $query->where('text', 'LIKE', '%' . $search)
            ->orWhere('text', 'LIKE', $search . '%')
            ->orWhere('text', 'LIKE', '%' . $search . '%');
    })
        ->where(function ($q) use ($lang) {
            if (!empty($lang))
                $q->where('link', 'LIKE', '%' . $lang . '%');
        })
        ->get();

    return $titleExactMatches
        ->merge($titleCloseMatches)
        ->merge($textExactMatches)
        ->merge($textCloseMatches)
        ->values();
});

/**
 * Upload CSV metadata page
 */
Route::get('/upload/meta/csv', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    return view('upload-csv');
});

/**
 * Upload CSV metadata :POST
 */
use App\Models\MetaTable;

Route::post('/upload/meta/csv', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    if (!request()->file)
        return view('upload-csv')->with([
            'error' => 'The csv file is not provided!'
        ]);

    $data = request()->file->getContent();

    if (empty($data))
        return view('upload-csv')->with([
            'error' => 'The csv file is empty!'
        ]);

    if (!request()->table)
        return view('upload-csv')->with([
            'error' => 'The table name is required!'
        ]);

    MetaTable::where('name', request()->table)
        ->delete();

    $bit = 0;
    foreach (explode("\r\n", $data) as $row) {
        if ($bit == 0) {
            $bit++;
            continue;
        }

        MetaTable::create([
            'name' => request()->table,
            'row' => json_encode(explode("\t", $row)),
        ]);
    }
});

/**
 * get meta data based on table name | parsed in strange way since PHP to be honest doesn't support UTF8
 */
Route::get('/meta', function () {
    if (!request()->table)
        return response('Please provide the table name!', 422);

    $rows = MetaTable::where('name', request()->table)
        ->get('row')
        ->pluck('row')
        ->toArray();

    $rows = array_map(function ($row) {
        return implode('|', json_decode($row));
    }, $rows);

    return implode('#', $rows);
});

/**
 * clear search index data
 */
Route::get('/clear-search-data', function () {
    SearchIndexData::truncate();
});


