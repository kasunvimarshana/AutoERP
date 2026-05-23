<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sequence\Presentation\Http\Controllers\SequenceController;

Route::prefix('api/sequence')
    ->middleware('api')
    ->name('sequence.')
    ->group(function (): void {
        Route::post('sequences/next', [SequenceController::class, 'next'])->name('sequences.next');
        Route::apiResource('sequences', SequenceController::class);
    });
