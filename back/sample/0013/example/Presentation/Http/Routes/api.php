<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog Context — API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by CatalogServiceProvider under the "api"
| middleware group with the prefix "api/v1".
|
| Full URLs:
|   GET    /api/v1/products
|   POST   /api/v1/products
|   GET    /api/v1/products/{id}
|
*/

Route::get('/products',       [ProductController::class, 'index']);
Route::post('/products',      [ProductController::class, 'store']);
Route::get('/products/{id}',  [ProductController::class, 'show']);
