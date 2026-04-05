<?php

use Illuminate\Support\Facades\Route;
use Modules\Barcode\Infrastructure\Http\Controllers\BarcodeDefinitionController;
use Modules\Barcode\Infrastructure\Http\Controllers\BarcodeScanController;
use Modules\Barcode\Infrastructure\Http\Controllers\BarcodePrintJobController;
use Modules\Barcode\Infrastructure\Http\Controllers\LabelTemplateController;

Route::prefix('api')->group(function () {

    // ── Barcode Definitions ───────────────────────────────────────────────────
    Route::get('barcodes',                          [BarcodeDefinitionController::class, 'index']);
    Route::post('barcodes',                         [BarcodeDefinitionController::class, 'store']);
    Route::get('barcodes/{id}',                     [BarcodeDefinitionController::class, 'show']);
    Route::get('barcodes/{id}/generate',            [BarcodeDefinitionController::class, 'generate']);
    Route::patch('barcodes/{id}/activate',          [BarcodeDefinitionController::class, 'activate']);
    Route::patch('barcodes/{id}/deactivate',        [BarcodeDefinitionController::class, 'deactivate']);
    Route::delete('barcodes/{id}',                  [BarcodeDefinitionController::class, 'destroy']);

    // ── Barcode Scans ─────────────────────────────────────────────────────────
    Route::get('barcode-scans',                     [BarcodeScanController::class, 'index']);
    Route::post('barcode-scans',                    [BarcodeScanController::class, 'store']);
    Route::get('barcode-scans/{id}',                [BarcodeScanController::class, 'show']);
    Route::delete('barcode-scans/{id}',             [BarcodeScanController::class, 'destroy']);

    // ── Label Templates ───────────────────────────────────────────────────────
    Route::get('barcode-label-templates',           [LabelTemplateController::class, 'index']);
    Route::post('barcode-label-templates',          [LabelTemplateController::class, 'store']);
    Route::get('barcode-label-templates/{id}',      [LabelTemplateController::class, 'show']);
    Route::put('barcode-label-templates/{id}',      [LabelTemplateController::class, 'update']);
    Route::patch('barcode-label-templates/{id}/activate',   [LabelTemplateController::class, 'activate']);
    Route::patch('barcode-label-templates/{id}/deactivate', [LabelTemplateController::class, 'deactivate']);
    Route::delete('barcode-label-templates/{id}',   [LabelTemplateController::class, 'destroy']);

    // ── Print Jobs ────────────────────────────────────────────────────────────
    Route::get('barcode-print-jobs',                [BarcodePrintJobController::class, 'index']);
    Route::post('barcode-print-jobs',               [BarcodePrintJobController::class, 'store']);
    Route::get('barcode-print-jobs/{id}',           [BarcodePrintJobController::class, 'show']);
    Route::post('barcode-print-jobs/{id}/process',  [BarcodePrintJobController::class, 'process']);
    Route::patch('barcode-print-jobs/{id}/cancel',  [BarcodePrintJobController::class, 'cancel']);
    Route::delete('barcode-print-jobs/{id}',        [BarcodePrintJobController::class, 'destroy']);
});
