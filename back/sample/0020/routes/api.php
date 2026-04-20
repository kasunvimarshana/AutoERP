<?php

use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\InventoryController;
use App\Modules\User\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
| Public Routes
*/
Route::post('/login', function (Request $request) {
    $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    return ['token' => $user->createToken('api')->plainTextToken];
});

/*
| Protected Routes (Tenant Isolated)
*/
Route::middleware('auth:sanctum')->group(function () {
    // Finance
    Route::post('/finance/journal-entries', [FinanceController::class, 'storeJournalEntry']);
    
    // Inventory
    Route::get('/inventory/lookup', [InventoryController::class, 'lookup']);
    Route::post('/inventory/products', [InventoryController::class, 'createProduct']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
