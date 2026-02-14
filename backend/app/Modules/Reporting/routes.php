<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Reporting\Http\Controllers\ReportingController;

Route::prefix('api/reporting')->middleware(['api'])->group(function () {
    // Sales Reports
    Route::get('/sales', [ReportingController::class, 'getSalesReport']);
    Route::get('/sales/trends', [ReportingController::class, 'getSalesTrends']);
    
    // Inventory Reports
    Route::get('/inventory', [ReportingController::class, 'getInventoryReport']);
    
    // Customer Reports
    Route::get('/customers', [ReportingController::class, 'getCustomerReport']);
    
    // KPI Reports
    Route::get('/revenue/kpis', [ReportingController::class, 'getRevenueKPIs']);
    
    // Top Performers
    Route::get('/top-products', [ReportingController::class, 'getTopProducts']);
    Route::get('/top-customers', [ReportingController::class, 'getTopCustomers']);
    
    // Dashboard
    Route::get('/dashboard', [ReportingController::class, 'getDashboardSummary']);
});
