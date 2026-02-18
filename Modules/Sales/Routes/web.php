<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Sales module.
|
*/

Route::get('/', function () {
    return view('sales::index');
});
