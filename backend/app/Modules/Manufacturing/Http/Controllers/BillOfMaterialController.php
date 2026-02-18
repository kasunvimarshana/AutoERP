<?php

namespace App\Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\DTOs\CreateBillOfMaterialDTO;
use App\Modules\Manufacturing\Services\BillOfMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bill of Material API Controller
 *
 * Handles HTTP requests for BOM management.
 */
class BillOfMaterialController extends BaseController
{
    public function __construct(
        protected BillOfMaterialService $bomService
    ) {}

    /**
     * Display a listing of BOMs
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $boms = $this->bomService->getAllBOMs($perPage);

        return $this->successResponse($boms, 'BOMs retrieved successfully');
    }

    /**
     * Store a newly created BOM
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'bom_number' => 'required|string|unique:bill_of_materials,bom_number',
            'version' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'effective_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.component_product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'items.*.scrap_factor' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        $dto = CreateBillOfMaterialDTO::fromArray($validated);
        $bom = $this->bomService->createBOM($dto);

        return $this->createdResponse($bom, 'BOM created successfully');
    }

    /**
     * Display the specified BOM
     */
    public function show(int $id): JsonResponse
    {
        $bom = $this->bomService->getBOMById($id);

        return $this->successResponse($bom, 'BOM retrieved successfully');
    }

    /**
     * Update the specified BOM
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'bom_number' => 'required|string|unique:bill_of_materials,bom_number,'.$id,
            'version' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'effective_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.component_product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'items.*.scrap_factor' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        $dto = CreateBillOfMaterialDTO::fromArray($validated);
        $bom = $this->bomService->updateBOM($id, $dto);

        return $this->successResponse($bom, 'BOM updated successfully');
    }

    /**
     * Remove the specified BOM
     */
    public function destroy(int $id): JsonResponse
    {
        $this->bomService->deleteBOM($id);

        return $this->successResponse(null, 'BOM deleted successfully');
    }

    /**
     * Create a new version of an existing BOM
     */
    public function createVersion(int $id): JsonResponse
    {
        $bom = $this->bomService->createNewVersion($id);

        return $this->createdResponse($bom, 'New BOM version created successfully');
    }

    /**
     * Get BOMs for a specific product
     */
    public function byProduct(int $productId): JsonResponse
    {
        $boms = $this->bomService->getBOMsForProduct($productId);

        return $this->successResponse($boms, 'BOMs retrieved successfully');
    }

    /**
     * Get latest active BOM for a product
     */
    public function latestActive(int $productId): JsonResponse
    {
        $bom = $this->bomService->getLatestActiveBOMForProduct($productId);

        return $this->successResponse($bom, 'Latest active BOM retrieved successfully');
    }

    /**
     * Search BOMs
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $boms = $this->bomService->searchBOMs($validated['query']);

        return $this->successResponse($boms, 'Search results retrieved successfully');
    }
}
