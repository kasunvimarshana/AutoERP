<?php

use Illuminate\Support\Facades\Route;

// Catch-all route for Vue Router (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
