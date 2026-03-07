<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    // -------------------------------------------------------------------------
    // GET /api/v1/inventory
    // -------------------------------------------------------------------------

    /**
     * List products with their current stock levels (tenant-scoped, paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $perPage  = min(max((int) $request->query('per_page', 15), 1), 100);

        $products = Product::byTenant($tenantId)
            ->with(['inventoryItems' => fn ($q) => $q->byTenant($tenantId)])
            ->when($request->query('category'), fn ($q, $cat) => $q->byCategory($cat))
            ->when($request->query('active') !== null, function ($q) use ($request) {
                $active = filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN);
                return $q->where('is_active', $active);
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $products->map(fn (Product $p) => $this->formatProduct($p)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/inventory/{id}
    // -------------------------------------------------------------------------

    /**
     * Get a single product with full inventory details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $product = Product::byTenant($tenantId)
            ->with(['inventoryItems.warehouse'])
            ->find($id);

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(['data' => $this->formatProduct($product, detailed: true)]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/inventory
    // -------------------------------------------------------------------------

    /**
     * Create a new product and its initial inventory record.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $data = $request->validate([
            'sku'                => ['required', 'string', 'max:100',
                Rule::unique('products')->where('tenant_id', $tenantId)],
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'category'           => ['required', 'string', 'max:100'],
            'unit_price'         => ['required', 'numeric', 'min:0'],
            'currency'           => ['nullable', 'string', 'size:3'],
            'is_active'          => ['nullable', 'boolean'],
            'metadata'           => ['nullable', 'array'],
            'warehouse_id'       => ['nullable', 'string', 'exists:warehouses,id'],
            'quantity_available' => ['nullable', 'integer', 'min:0'],
            'reorder_level'      => ['nullable', 'integer', 'min:0'],
            'max_stock_level'    => ['nullable', 'integer', 'min:0'],
            'unit_of_measure'    => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'tenant_id'   => $tenantId,
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'category'    => $data['category'],
                'unit_price'  => $data['unit_price'],
                'currency'    => $data['currency'] ?? 'USD',
                'is_active'   => $data['is_active'] ?? true,
                'metadata'    => $data['metadata'] ?? null,
            ]);

            $inventoryItem = InventoryItem::create([
                'product_id'         => $product->id,
                'tenant_id'          => $tenantId,
                'warehouse_id'       => $data['warehouse_id'] ?? null,
                'quantity_available' => $data['quantity_available'] ?? 0,
                'quantity_reserved'  => 0,
                'quantity_sold'      => 0,
                'reorder_level'      => $data['reorder_level'] ?? 10,
                'max_stock_level'    => $data['max_stock_level'] ?? 1000,
                'unit_of_measure'    => $data['unit_of_measure'] ?? 'unit',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Product created.',
                'data'    => $this->formatProduct($product->load('inventoryItems')),
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[InventoryController] store failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create product.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/inventory/{id}
    // -------------------------------------------------------------------------

    /**
     * Update inventory levels for a product.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $product = Product::byTenant($tenantId)->find($id);

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'category'           => ['sometimes', 'string', 'max:100'],
            'unit_price'         => ['sometimes', 'numeric', 'min:0'],
            'is_active'          => ['sometimes', 'boolean'],
            'metadata'           => ['nullable', 'array'],
            'warehouse_id'       => ['nullable', 'string', 'exists:warehouses,id'],
            'quantity_available' => ['sometimes', 'integer', 'min:0'],
            'reorder_level'      => ['sometimes', 'integer', 'min:0'],
            'max_stock_level'    => ['sometimes', 'integer', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            $product->fill(array_intersect_key($data, array_flip([
                'name', 'description', 'category', 'unit_price', 'is_active', 'metadata',
            ])));
            $product->save();

            // Update inventory item if stock fields provided.
            $stockFields = array_intersect_key($data, array_flip([
                'quantity_available', 'reorder_level', 'max_stock_level', 'warehouse_id',
            ]));

            if (!empty($stockFields)) {
                $inventoryItem = $product->inventoryItems()->first();
                if ($inventoryItem) {
                    $inventoryItem->fill($stockFields);
                    $inventoryItem->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated.',
                'data'    => $this->formatProduct($product->load('inventoryItems')),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[InventoryController] update failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update product.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/inventory/check-availability
    // -------------------------------------------------------------------------

    /**
     * Check stock availability for one or more SKUs without reserving.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $data = $request->validate([
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.sku'            => ['required', 'string'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.warehouse_id'   => ['nullable', 'string'],
        ]);

        $result = $this->inventoryService->checkAvailability($data['items'], $tenantId);

        return response()->json(['data' => $result]);
    }

    // -------------------------------------------------------------------------
    // GET /api/health
    // -------------------------------------------------------------------------

    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'service' => 'inventory-service']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): string
    {
        return (string) ($request->header('X-Tenant-ID') ?? $request->query('tenant_id', 'default'));
    }

    private function formatProduct(Product $product, bool $detailed = false): array
    {
        $base = [
            'id'          => $product->id,
            'tenant_id'   => $product->tenant_id,
            'sku'         => $product->sku,
            'name'        => $product->name,
            'category'    => $product->category,
            'unit_price'  => $product->unit_price,
            'currency'    => $product->currency,
            'is_active'   => $product->is_active,
            'created_at'  => $product->created_at,
            'updated_at'  => $product->updated_at,
        ];

        if ($detailed) {
            $base['description'] = $product->description;
            $base['metadata']    = $product->metadata;
        }

        if ($product->relationLoaded('inventoryItems')) {
            $base['inventory'] = $product->inventoryItems->map(fn (InventoryItem $item) => [
                'id'                 => $item->id,
                'warehouse_id'       => $item->warehouse_id,
                'quantity_available' => $item->quantity_available,
                'quantity_reserved'  => $item->quantity_reserved,
                'quantity_sold'      => $item->quantity_sold,
                'reorder_level'      => $item->reorder_level,
                'max_stock_level'    => $item->max_stock_level,
                'unit_of_measure'    => $item->unit_of_measure,
            ])->values();
        }

        return $base;
    }
}
