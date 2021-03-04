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

Route::get('/', function () {
    return redirect()->to('/login');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/logout', function () {
    auth()->logout();
    return redirect()->to('/login');
});

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

Route::get('/upload', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    return view('upload');
});

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

Route::get('/tables', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    return view('tables')->with([
        'tables' => \App\Models\Table::all(),
    ]);
});

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
        'rows' => explode("\n", file_get_contents($table->name . '.csv')),
        'table' => $table,
    ]);
});

Route::get('/clear', function () {
    if (!auth()->check())
        return redirect()->to('/login');

    \App\Models\Table::truncate();

    return redirect()->to('/tables');
});

Route::get('/fetch-data', function () {
    $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');

    $data = [];
    foreach ($tables as $key => $tableName) {
        $tableData = \Illuminate\Support\Facades\DB::select('SELECT * FROM ' . $tableName->Tables_in_ktc);
        $data[$tableName->Tables_in_ktc] = $tableData;
    }

    dd($data);
});

Route::get('/shop', function () {
    return \Illuminate\Support\Facades\DB::select(\Illuminate\Support\Facades\DB::raw('
    SELECT tblArticleCategories.pkArticleCategory,
                                       tblArticleCategories.articleCategory,
                                       tblArticleCategoryDetails.articleCategoryDetailName,
                                       tblArticleCategories.articleCategorySortKey,
                                       tblArticleCategories.articleCategoryImageKey,
                                       tblArticleCategoryDetails.articleCategoryButtonText,
                                       tblArticleCategoryDetails.languageCode
FROM tblArticleCategories INNER JOIN tblArticleCategoryDetails ON tblArticleCategories.pkArticleCategory = tblArticleCategoryDetails.fkArticleCategory
WHERE (((tblArticleCategoryDetails.languageCode)="de-CH"))
                                       ORDER BY tblArticleCategories.articleCategorySortKey
    '));
});



