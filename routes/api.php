<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'success' => true,
    'message' => 'API is healthy',
    'data' => [
        'service' => 'ERP SaaS Platform',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ],
    'errors' => null,
]));
