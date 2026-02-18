<?php

use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
        'checks' => [],
    ];
    
    // Check database connectivity
    try {
        DB::connection()->getPdo();
        $health['checks']['database'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['database'] = 'failed';
        $health['status'] = 'unhealthy';
    }
    
    // Check Redis connectivity
    try {
        Cache::store('redis')->get('health_check');
        $health['checks']['redis'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['redis'] = 'failed';
        $health['status'] = 'unhealthy';
    }
    
    // Check distributed lock store
    try {
        Cache::store('lock')->get('health_check');
        $health['checks']['distributed_locks'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['distributed_locks'] = 'failed';
        $health['status'] = 'degraded';
    }
    
    // Check queue status
    try {
        $defaultQueueSize = Cache::store('redis')->get('queue:default:size', 0);
        $health['checks']['queue'] = $defaultQueueSize < 1000 ? 'ok' : 'degraded';
        $health['queue_depth'] = $defaultQueueSize;
    } catch (\Exception $e) {
        $health['checks']['queue'] = 'unknown';
    }
    
    $statusCode = match($health['status']) {
        'healthy' => 200,
        'degraded' => 200,
        'unhealthy' => 503,
        default => 503,
    };
    
    return response()->json($health, $statusCode);
});

// API Documentation routes (no authentication required)
Route::prefix('v1/documentation')->group(function () {
    Route::get('/spec', [DocumentationController::class, 'specification']);
    Route::get('/modules/{module}', [DocumentationController::class, 'moduleSpecification']);
});
