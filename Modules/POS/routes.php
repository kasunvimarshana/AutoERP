<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Presentation\Controllers\PosTerminalController;
use Modules\POS\Presentation\Controllers\PosSessionController;
use Modules\POS\Presentation\Controllers\PosOrderController;
use Modules\POS\Presentation\Controllers\LoyaltyProgramController;
use Modules\POS\Presentation\Controllers\LoyaltyCardController;
use Modules\POS\Presentation\Controllers\PosDiscountController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('pos/terminals', PosTerminalController::class);
    Route::apiResource('pos/sessions', PosSessionController::class);
    Route::post('pos/sessions/{id}/close', [PosSessionController::class, 'close']);
    Route::apiResource('pos/orders', PosOrderController::class);
    Route::post('pos/orders/{id}/refund', [PosOrderController::class, 'refund']);
    Route::get('pos/orders/{id}/payments', [PosOrderController::class, 'payments']);
    Route::get('pos/loyalty-programs', [LoyaltyProgramController::class, 'index']);
    Route::post('pos/loyalty-programs', [LoyaltyProgramController::class, 'store']);
    Route::get('pos/loyalty-programs/{id}', [LoyaltyProgramController::class, 'show']);
    Route::put('pos/loyalty-programs/{id}', [LoyaltyProgramController::class, 'update']);
    Route::delete('pos/loyalty-programs/{id}', [LoyaltyProgramController::class, 'destroy']);
    Route::get('pos/loyalty-cards', [LoyaltyCardController::class, 'index']);
    Route::post('pos/loyalty-cards/accrue', [LoyaltyCardController::class, 'accrue']);
    Route::get('pos/loyalty-cards/{id}', [LoyaltyCardController::class, 'show']);
    Route::post('pos/loyalty-cards/{id}/redeem', [LoyaltyCardController::class, 'redeem']);
    Route::get('pos/discounts', [PosDiscountController::class, 'index']);
    Route::post('pos/discounts', [PosDiscountController::class, 'store']);
    Route::get('pos/discounts/{id}', [PosDiscountController::class, 'show']);
    Route::put('pos/discounts/{id}', [PosDiscountController::class, 'update']);
    Route::delete('pos/discounts/{id}', [PosDiscountController::class, 'destroy']);
    Route::post('pos/discounts/{code}/validate', [PosDiscountController::class, 'validateCode']);
});
