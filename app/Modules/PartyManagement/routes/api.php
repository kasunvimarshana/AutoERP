<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PartyManagement\Infrastructure\Http\Controllers\AssetOwnershipController;
use Modules\PartyManagement\Infrastructure\Http\Controllers\PartyController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {

    // Party routes
    Route::prefix('parties')->group(function (): void {
        Route::get('/', [PartyController::class, 'index']);
        Route::post('/', [PartyController::class, 'store']);
        Route::get('/{party}', [PartyController::class, 'show']);
        Route::put('/{party}', [PartyController::class, 'update']);
        Route::delete('/{party}', [PartyController::class, 'destroy']);
    });

    // Asset ownership routes
    Route::prefix('asset-ownerships')->group(function (): void {
        Route::get('/', [AssetOwnershipController::class, 'index']);
        Route::post('/', [AssetOwnershipController::class, 'store']);
        Route::get('/{assetOwnership}', [AssetOwnershipController::class, 'show']);
        Route::put('/{assetOwnership}', [AssetOwnershipController::class, 'update']);
    });

    Route::get('parties/{party}/ownerships', [AssetOwnershipController::class, 'byParty']);
    Route::get('assets/{asset}/ownerships', [AssetOwnershipController::class, 'byAsset']);
});
