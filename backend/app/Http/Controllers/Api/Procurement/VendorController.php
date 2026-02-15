<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Procurement\VendorResource;
use App\Modules\Procurement\Services\ProcurementService;
use App\Http\Requests\Procurement\StoreVendorRequest;
use App\Http\Requests\Procurement\UpdateVendorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VendorController extends BaseController
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    /**
     * Display a listing of vendors.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $vendors = $this->procurementService->getAll([], $perPage);

            return $this->paginatedResponse(
                VendorResource::collection($vendors),
                'Vendors retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve vendors: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created vendor.
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $vendor = $this->procurementService->createVendor($validated);

            return $this->successResponse(
                new VendorResource($vendor),
                'Vendor created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create vendor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified vendor.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $vendor = $this->procurementService->getById($id);

            if (!$vendor) {
                return $this->errorResponse('Vendor not found', [], 404);
            }

            $this->authorize('view', $vendor);

            return $this->successResponse(
                new VendorResource($vendor->load(['contacts', 'purchaseOrders'])),
                'Vendor retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve vendor: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified vendor.
     */
    public function update(UpdateVendorRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $vendor = $this->procurementService->updateVendor($id, $validated);

            return $this->successResponse(
                new VendorResource($vendor),
                'Vendor updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update vendor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified vendor.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $vendor = $this->procurementService->getById($id);

            if (!$vendor) {
                return $this->errorResponse('Vendor not found', [], 404);
            }

            $this->authorize('delete', $vendor);

            $this->procurementService->delete($id);

            return $this->successResponse(
                null,
                'Vendor deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete vendor: ' . $e->getMessage());
        }
    }

    /**
     * Search vendors.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query', '');
            $filters = $request->input('filters', []);

            $vendors = $this->procurementService->searchVendors($query, $filters);

            return $this->successResponse(
                VendorResource::collection($vendors),
                'Vendors searched successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search vendors: ' . $e->getMessage());
        }
    }

    /**
     * Get vendor statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->procurementService->getVendorStatistics();

            return $this->successResponse(
                $statistics,
                'Vendor statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve vendor statistics: ' . $e->getMessage());
        }
    }
}
