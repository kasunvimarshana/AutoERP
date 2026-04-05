<?php
use Illuminate\Support\Facades\Route;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptController;
Route::prefix('api')->group(function () {
    Route::get('goods-receipts', [GoodsReceiptController::class, 'index']);
    Route::post('goods-receipts', [GoodsReceiptController::class, 'store']);
    Route::get('goods-receipts/{id}', [GoodsReceiptController::class, 'show']);
    Route::post('goods-receipts/{id}/inspect', [GoodsReceiptController::class, 'inspect']);
    Route::post('goods-receipts/{id}/put-away', [GoodsReceiptController::class, 'putAway']);
    Route::delete('goods-receipts/{id}', [GoodsReceiptController::class, 'destroy']);
});
