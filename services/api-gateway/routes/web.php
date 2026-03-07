<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| The API Gateway exposes no web-facing routes; this file satisfies the
| RouteServiceProvider requirement.
*/

Route::get('/', fn () => response()->json(['service' => 'LaravelSAGA API Gateway', 'status' => 'ok']));
