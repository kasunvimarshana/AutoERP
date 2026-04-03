<?php

use Illuminate\Support\Facades\Route;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptController;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptLineController;

Route::apiResource('goods-receipts', GoodsReceiptController::class);
Route::post('goods-receipts/{id}/receive', [GoodsReceiptController::class, 'receive']);
Route::post('goods-receipts/{id}/approve', [GoodsReceiptController::class, 'approve']);
Route::post('goods-receipts/{id}/cancel', [GoodsReceiptController::class, 'cancel']);
Route::post('goods-receipts/{id}/inspect', [GoodsReceiptController::class, 'inspect']);
Route::post('goods-receipts/{id}/put-away', [GoodsReceiptController::class, 'putAway']);
Route::apiResource('goods-receipt-lines', GoodsReceiptLineController::class);
