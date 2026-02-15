<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\Services\BillOfMaterialService;
use App\Http\Requests\Manufacturing\StoreBOMRequest;
use App\Http\Requests\Manufacturing\UpdateBOMRequest;
use App\Http\Resources\Manufacturing\BOMResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * BillOfMaterialController
 * 
 * Handles HTTP requests for Bill of Materials operations
 */
class BillOfMaterialController extends BaseController
{
    protected BillOfMaterialService $bomService;

    /**
     * BillOfMaterialController constructor.
     *
     * @param BillOfMaterialService $bomService
     */
    public function __construct(BillOfMaterialService $bomService)
    {
        $this->bomService = $bomService;
    }

    /**
     * Display a listing of BOMs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            
            $filters = $request->only([
                'search',
                'product_id',
                'status',
                'is_default',
                'version'
            ]);

            if (!empty($filters)) {
                $boms = $this->bomService->search($filters);
                return $this->successResponse(
                    BOMResource::collection($boms),
                    'BOMs retrieved successfully'
                );
            }

            $boms = $this->bomService->getAll([], $perPage);

            return $this->paginatedResponse(
                BOMResource::collection($boms),
                'BOMs retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve BOMs: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created BOM.
     *
     * @param StoreBOMRequest $request
     * @return JsonResponse
     */
    public function store(StoreBOMRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $bom = $this->bomService->create($validated);

            return $this->successResponse(
                new BOMResource($bom),
                'BOM created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create BOM: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified BOM.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $bom = $this->bomService->getById($id);

            return $this->successResponse(
                new BOMResource($bom),
                'BOM retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve BOM: ' . $e->getMessage(), [], 404);
        }
    }

    /**
     * Update the specified BOM.
     *
     * @param UpdateBOMRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateBOMRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $bom = $this->bomService->update($id, $validated);

            return $this->successResponse(
                new BOMResource($bom),
                'BOM updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update BOM: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified BOM.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->bomService->delete($id);

            return $this->successResponse(null, 'BOM deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete BOM: ' . $e->getMessage());
        }
    }

    /**
     * Activate a BOM.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $bom = $this->bomService->activate($id);

            return $this->successResponse(
                new BOMResource($bom),
                'BOM activated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate BOM: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a BOM.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $bom = $this->bomService->deactivate($id);

            return $this->successResponse(
                new BOMResource($bom),
                'BOM deactivated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate BOM: ' . $e->getMessage());
        }
    }

    /**
     * Get BOMs for a specific product.
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function getByProduct(Request $request, int $productId): JsonResponse
    {
        try {
            $boms = $this->bomService->getByProduct($productId);

            return $this->successResponse(
                BOMResource::collection($boms),
                'BOMs retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve BOMs: ' . $e->getMessage());
        }
    }

    /**
     * Calculate material requirements for a BOM.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function calculateMaterials(Request $request, int $id): JsonResponse
    {
        try {
            $quantity = $request->input('quantity', 1);
            $requirements = $this->bomService->calculateMaterialRequirements($id, $quantity);

            return $this->successResponse(
                $requirements,
                'Material requirements calculated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate material requirements: ' . $e->getMessage());
        }
    }
}
