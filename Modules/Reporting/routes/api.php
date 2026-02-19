<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\AnalyticsController;
use Modules\Reporting\Http\Controllers\DashboardController;
use Modules\Reporting\Http\Controllers\ReportController;
use Modules\Reporting\Http\Controllers\WidgetController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->group(function () {

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/', [ReportController::class, 'store']);
        Route::get('/templates', [ReportController::class, 'templates']);
        Route::get('/{report}', [ReportController::class, 'show']);
        Route::put('/{report}', [ReportController::class, 'update']);
        Route::delete('/{report}', [ReportController::class, 'destroy']);

        // Report actions
        Route::post('/{report}/execute', [ReportController::class, 'execute']);
        Route::post('/{report}/export', [ReportController::class, 'export']);
        Route::post('/{report}/publish', [ReportController::class, 'publish']);
        Route::post('/{report}/archive', [ReportController::class, 'archive']);
    });

    // Dashboards
    Route::prefix('dashboards')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::post('/', [DashboardController::class, 'store']);
        Route::get('/default', [DashboardController::class, 'getDefault']);
        Route::get('/{dashboard}', [DashboardController::class, 'show']);
        Route::put('/{dashboard}', [DashboardController::class, 'update']);
        Route::delete('/{dashboard}', [DashboardController::class, 'destroy']);

        // Dashboard actions
        Route::get('/{dashboard}/render', [DashboardController::class, 'render']);
        Route::post('/{dashboard}/set-default', [DashboardController::class, 'setDefault']);
        Route::post('/{dashboard}/clone', [DashboardController::class, 'clone']);
    });

    // Widgets
    Route::prefix('widgets')->group(function () {
        Route::post('/', [WidgetController::class, 'store']);
        Route::get('/{widget}', [WidgetController::class, 'show']);
        Route::put('/{widget}', [WidgetController::class, 'update']);
        Route::delete('/{widget}', [WidgetController::class, 'destroy']);
        Route::patch('/{widget}/position', [WidgetController::class, 'updatePosition']);
    });

    // Widget reordering
    Route::post('/dashboards/{dashboardId}/widgets/reorder', [WidgetController::class, 'reorder']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/sales', [AnalyticsController::class, 'sales']);
        Route::get('/inventory', [AnalyticsController::class, 'inventory']);
        Route::get('/crm', [AnalyticsController::class, 'crm']);
        Route::get('/financial', [AnalyticsController::class, 'financial']);
        Route::get('/top-products', [AnalyticsController::class, 'topProducts']);
        Route::get('/customers', [AnalyticsController::class, 'customers']);
        Route::get('/trend', [AnalyticsController::class, 'trend']);
    });
});
