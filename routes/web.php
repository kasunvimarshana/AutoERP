<?php

use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation UI
Route::get('/docs', [DocumentationController::class, 'index']);
