<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Presentation\Controllers\KbArticleController;
use Modules\Helpdesk\Presentation\Controllers\KbCategoryController;
use Modules\Helpdesk\Presentation\Controllers\TicketCategoryController;
use Modules\Helpdesk\Presentation\Controllers\TicketController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('helpdesk/categories', TicketCategoryController::class);
    Route::apiResource('helpdesk/tickets', TicketController::class)->except(['update']);
    Route::post('helpdesk/tickets/{id}/assign', [TicketController::class, 'assign']);
    Route::post('helpdesk/tickets/{id}/resolve', [TicketController::class, 'resolve']);
    Route::post('helpdesk/tickets/{id}/close', [TicketController::class, 'close']);

    // Knowledge Base — authenticated (agent) routes
    Route::apiResource('helpdesk/kb/categories', KbCategoryController::class);
    Route::apiResource('helpdesk/kb/articles', KbArticleController::class)->except(['update']);
    Route::post('helpdesk/kb/articles/{id}/publish', [KbArticleController::class, 'publish']);
    Route::post('helpdesk/kb/articles/{id}/archive', [KbArticleController::class, 'archive']);
    Route::post('helpdesk/kb/articles/{id}/rate', [KbArticleController::class, 'rate']);
});

// Knowledge Base — public endpoint (no auth required)
Route::prefix('api/v1')->middleware(['api'])->group(function () {
    Route::get('helpdesk/kb/public/{tenantId}', [KbArticleController::class, 'publicIndex']);
});
