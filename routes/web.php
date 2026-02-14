<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to AutoERP API',
        'version' => 'v1',
        'status' => 'operational'
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});
