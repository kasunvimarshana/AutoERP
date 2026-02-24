<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA for all non-API, non-Horizon, non-telescope routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|horizon|telescope).*');
