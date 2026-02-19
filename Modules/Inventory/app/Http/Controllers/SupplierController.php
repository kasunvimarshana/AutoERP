<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Requests\StoreSupplierRequest;
use Modules\Inventory\Requests\UpdateSupplierRequest;
use Modules\Inventory\Resources\SupplierResource;
use Modules\Inventory\Services\SupplierService;

/**
 * Supplier Controller
 *
 * Handles HTTP requests for Supplier operations
 */
class SupplierController extends Controller
{
    /**
     * SupplierController constructor
     */
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'paginate', 'per_page']);

        $suppliers = $this->supplierService->getAll($filters);

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully',
            'data' => SupplierResource::collection($suppliers),
        ]);
    }

    /**
     * Store a newly created supplier
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Display the specified supplier
     */
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier retrieved successfully',
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update the specified supplier
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Remove the specified supplier
     */
    public function destroy(int $id): JsonResponse
    {
        $this->supplierService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Search suppliers
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $suppliers = $this->supplierService->search($filters);

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully',
            'data' => SupplierResource::collection($suppliers),
        ]);
    }
}
