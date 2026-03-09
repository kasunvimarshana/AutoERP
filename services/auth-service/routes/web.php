<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Auth Service
|--------------------------------------------------------------------------
| These routes handle OAuth callbacks and minimal web-facing endpoints.
*/

Route::get('/', function () {
    return response()->json([
        'service' => config('app.name'),
        'version' => '1.0.0',
        'status'  => 'running',
    ]);
});
