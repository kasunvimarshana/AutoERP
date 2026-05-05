<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ReturnRefund\Infrastructure\Http\Controllers\ReturnRefundController;

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])->prefix('return-refund')->group(function (): void {
    Route::post('/process', [ReturnRefundController::class, 'process']);
});
