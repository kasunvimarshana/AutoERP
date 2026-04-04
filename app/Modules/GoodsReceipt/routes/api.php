<?php
use Illuminate\Support\Facades\Route;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptController;

Route::prefix('goods-receipts')->group(function () {
    Route::get('/',                  [GoodsReceiptController::class, 'index']);
    Route::post('/',                 [GoodsReceiptController::class, 'store']);
    Route::get('/{id}',              [GoodsReceiptController::class, 'show']);
    Route::patch('/{id}',            [GoodsReceiptController::class, 'update']);
    Route::delete('/{id}',           [GoodsReceiptController::class, 'destroy']);
    Route::post('/{id}/inspect',     [GoodsReceiptController::class, 'inspect']);
    Route::post('/{id}/put-away',    [GoodsReceiptController::class, 'putAway']);
    Route::post('/{id}/complete',    [GoodsReceiptController::class, 'complete']);
});
