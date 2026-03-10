<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\ProductServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function __construct(private readonly ProductServiceClient $productClient) {}

    public function index(Request $request): JsonResponse
    {
        $query = Inventory::query();

        // Direct filters
        if ($request->filled('warehouse_location')) {
            $query->where('warehouse_location', 'like', '%' . $request->warehouse_location . '%');
        }

        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', (int) $request->min_quantity);
        }

        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', (int) $request->max_quantity);
        }

        // Cross-service filters: call Product Service to get matching product IDs
        $crossServiceFilters = [];

        if ($request->filled('product_name')) {
            $crossServiceFilters['name'] = $request->product_name;
        }

        if ($request->filled('product_code')) {
            $crossServiceFilters['code'] = $request->product_code;
        }

        if ($request->filled('product_category')) {
            $crossServiceFilters['category'] = $request->product_category;
        }

        if ($request->filled('product_search')) {
            $crossServiceFilters['search'] = $request->product_search;
        }

        if (!empty($crossServiceFilters)) {
            $products   = $this->productClient->getProductsByFilters($crossServiceFilters);
            $productIds = array_column($products, 'id');

            if (empty($productIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory retrieved successfully',
                    'data'    => ['inventory' => []],
                ]);
            }

            $query->whereIn('product_id', $productIds);
        }

        $perPage   = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully',
            'data'    => [
                'inventory'  => $paginator->items(),
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
            'product_id'         => 'required|integer|min:1',
            'product_name'       => 'required|string|max:255',
            'product_code'       => 'required|string|max:100',
            'product_category'   => 'required|string|max:100',
            'quantity'           => 'required|integer|min:0',
            'reserved_quantity'  => 'nullable|integer|min:0',
            'warehouse_location' => 'nullable|string|max:255',
            'reorder_level'      => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $inventory = Inventory::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventory created successfully',
            'data'    => ['inventory' => $inventory],
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
            ], 404);
        }

        // Enrich with live product data if Product Service is reachable
        $product = $this->productClient->getProduct((int) $inventory->product_id);

        $data = $inventory->toArray();

        if ($product) {
            $data['product'] = $product;
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully',
            'data'    => ['inventory' => $data],
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

        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'product_id'         => 'sometimes|integer|min:1',
            'product_name'       => 'sometimes|string|max:255',
            'product_code'       => 'sometimes|string|max:100',
            'product_category'   => 'sometimes|string|max:100',
            'quantity'           => 'sometimes|integer|min:0',
            'reserved_quantity'  => 'sometimes|integer|min:0',
            'warehouse_location' => 'nullable|string|max:255',
            'reorder_level'      => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $inventory->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'data'    => ['inventory' => $inventory],
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

        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
            ], 404);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory deleted successfully',
        ]);
    }

    public function reserve(Request $request, $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $qty = (int) $request->quantity;

        if ($inventory->available_quantity < $qty) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient available quantity',
                'data'    => [
                    'available_quantity' => $inventory->available_quantity,
                    'requested_quantity' => $qty,
                ],
            ], 422);
        }

        $inventory->reserved_quantity += $qty;
        $inventory->save();

        return response()->json([
            'success' => true,
            'message' => 'Quantity reserved successfully',
            'data'    => ['inventory' => $inventory],
        ]);
    }

    public function release(Request $request, $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $qty = (int) $request->quantity;

        if ($inventory->reserved_quantity < $qty) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot release more than reserved quantity',
                'data'    => [
                    'reserved_quantity'  => $inventory->reserved_quantity,
                    'requested_quantity' => $qty,
                ],
            ], 422);
        }

        $inventory->reserved_quantity -= $qty;
        $inventory->save();

        return response()->json([
            'success' => true,
            'message' => 'Quantity released successfully',
            'data'    => ['inventory' => $inventory],
        ]);
    }

    public function getByProductId(Request $request, $productId): JsonResponse
    {
        $inventory = Inventory::where('product_id', (int) $productId)->get();

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully',
            'data'    => ['inventory' => $inventory],
        ]);
    }
}
