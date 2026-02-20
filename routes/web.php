<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA for all non-API routes (catch-all for client-side routing)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
