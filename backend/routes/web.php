<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => config('app.name'),
        'version' => '1.0.0',
        'docs'    => url('/api/v1/health'),
    ]);
});
