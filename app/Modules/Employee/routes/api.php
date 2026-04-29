<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Employee\Infrastructure\Http\Controllers\EmployeeController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('employees', EmployeeController::class);
});
