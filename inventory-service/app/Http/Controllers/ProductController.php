<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $products = Product::query()
            ->select(['id', 'sku', 'name', 'description', 'price', 'stock_quantity', 'reserved_quantity'])
            ->orderBy('name')
            ->paginate($perPage);

        $items = collect($products->items())->map(fn (Product $p) => array_merge(
            $p->toArray(),
            ['available_stock' => $p->getAvailableStock()]
        ))->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'data' => array_merge($product->toArray(), [
                'available_stock' => $product->getAvailableStock(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku'            => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'price'          => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create($validated);

        Log::info('[Product] Created', ['product_id' => $product->id, 'sku' => $product->sku]);

        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'sku'            => ['sometimes', 'string', 'max:100', "unique:products,sku,{$id}"],
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'price'          => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($product->reserved_quantity > 0) {
            return response()->json([
                'message' => 'Cannot delete product with active reservations',
            ], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted'], 200);
    }

    public function stockUpdate(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason'     => ['required', 'string', 'max:255'],
        ]);

        $newQuantity = $product->stock_quantity + $validated['adjustment'];

        if ($newQuantity < 0) {
            return response()->json(['message' => 'Stock adjustment would result in negative stock'], 422);
        }

        $product->update(['stock_quantity' => $newQuantity]);

        Log::info('[Product] Stock adjusted', [
            'product_id' => $id,
            'adjustment' => $validated['adjustment'],
            'new_stock'  => $newQuantity,
            'reason'     => $validated['reason'],
        ]);

        return response()->json([
            'data' => array_merge($product->fresh()->toArray(), [
                'available_stock' => $product->fresh()->getAvailableStock(),
            ]),
        ]);
    }
}
