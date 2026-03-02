<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Metadata\Interfaces\Http\Controllers\MetadataController;

/*
|--------------------------------------------------------------------------
| Metadata Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/metadata/fields
|
*/

Route::middleware('auth:api')->prefix('api/v1/metadata')->name('metadata.')->group(function (): void {
    Route::apiResource('fields', MetadataController::class);
});
