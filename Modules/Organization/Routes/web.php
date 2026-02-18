<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('organization')->group(function () {
    Route::get('/', function () {
        return view('organization::index');
    });
});
