<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Application\Services\AIDCService;
use App\Modules\Inventory\Domain\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller {
    public function __construct(private AIDCService $aidcService) {}

    public function lookup(Request $request): JsonResponse {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['error' => 'Code parameter is required'], 400);
        }

        $entity = $this->aidcService->findByAny($code);

        if (!$entity) {
            return response()->json(['error' => 'Identifier not found'], 404);
        }

        return response()->json([
            'type' => class_basename($entity),
            'data' => $entity
        ]);
    }

    public function createProduct(Request $request): JsonResponse {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string',
            'uom' => 'required|string',
            'barcode' => 'nullable|string'
        ]);

        $product = Product::create($validated);

        if ($request->filled('barcode')) {
            $this->aidcService->assignIdentifier($request->barcode, $product);
        }

        return response()->json($product, 201);
    }
}
