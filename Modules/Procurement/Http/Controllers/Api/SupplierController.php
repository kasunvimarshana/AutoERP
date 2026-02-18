<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StoreSupplierRequest;
use Modules\Procurement\Http\Requests\UpdateSupplierRequest;
use Modules\Procurement\Http\Resources\SupplierResource;
use Modules\Procurement\Services\SupplierService;

/**
 * Supplier API Controller
 *
 * Handles API requests for supplier management.
 */
class SupplierController extends Controller
{
    /**
     * SupplierController constructor.
     */
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $suppliers = $this->supplierService->getPaginated($perPage);

        return SupplierResource::collection($suppliers)->response();
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);

        if (! $supplier) {
            return response()->json([
                'message' => 'Supplier not found',
            ], 404);
        }

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->update($id, $request->validated());

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->supplierService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Supplier not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Supplier deleted successfully',
        ], 200);
    }

    /**
     * Search suppliers.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $suppliers = $this->supplierService->search($query);

        return SupplierResource::collection($suppliers)->response();
    }

    /**
     * Get suppliers by rating.
     */
    public function byRating(Request $request): JsonResponse
    {
        $minRating = $request->input('min_rating', 0);
        $suppliers = $this->supplierService->getSuppliersByRating((float) $minRating);

        return SupplierResource::collection($suppliers)->response();
    }

    /**
     * Rate a supplier.
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
        ]);

        $supplier = $this->supplierService->rateSupplier($id, (float) $request->input('rating'));

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Evaluate supplier performance.
     */
    public function evaluate(int $id): JsonResponse
    {
        $performance = $this->supplierService->evaluateSupplierPerformance($id);

        return response()->json($performance);
    }
}
