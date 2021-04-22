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

/**
 * get data from database based on provided in request query
 * request: ['query_string' => String]
 *
 * if no query provided, return validation error..
 */
Route::get('/data', function () {
    if (empty(request()->query_string))
        return response('`query_string` is required!', 422);

    return \Illuminate\Support\Facades\DB::select(
        \Illuminate\Support\Facades\DB::raw(urldecode(request()->query_string))
    );
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
use Illuminate\Support\Facades\DB;

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



