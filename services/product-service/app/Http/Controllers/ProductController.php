<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', $request->code);
        }

        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('code', 'like', '%' . $term . '%')
                  ->orWhere('description', 'like', '%' . $term . '%')
                  ->orWhere('category', 'like', '%' . $term . '%');
            });
        }

        $perPage   = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data'    => [
                'products'   => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (($user['role'] ?? '') !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:100|unique:products,code',
            'category'       => 'required|string|max:100',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'image_url'      => 'nullable|string|max:500',
            'is_active'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product = Product::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => ['product' => $product],
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data'    => ['product' => $product],
        ]);
    }

    public function showByCode(Request $request, $code): JsonResponse
    {
        $product = Product::where('code', $code)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data'    => ['product' => $product],
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (($user['role'] ?? '') !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: admin access required',
            ], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|required|string|max:255',
            'code'           => 'sometimes|required|string|max:100|unique:products,code,' . $id,
            'category'       => 'sometimes|required|string|max:100',
            'description'    => 'nullable|string',
            'price'          => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'image_url'      => 'nullable|string|max:500',
            'is_active'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => ['product' => $product->fresh()],
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (($user['role'] ?? '') !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: admin access required',
            ], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function bulkGet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $products = Product::whereIn('id', $request->ids)->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data'    => ['products' => $products],
        ]);
    }
}
