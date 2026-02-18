<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\StoreUnitOfMeasureRequest;
use Modules\Inventory\Http\Requests\UpdateUnitOfMeasureRequest;
use Modules\Inventory\Http\Resources\UnitOfMeasureResource;
use Modules\Inventory\Services\UnitOfMeasureService;

/**
 * Unit of Measure API Controller
 *
 * Handles API requests for unit of measure management.
 */
class UnitOfMeasureController extends Controller
{
    /**
     * UnitOfMeasureController constructor.
     */
    public function __construct(
        protected UnitOfMeasureService $uomService
    ) {}

    /**
     * Display a listing of units of measure.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $category = $request->input('category');

        if ($category) {
            $uoms = $this->uomService->getByCategory($category);
            return UnitOfMeasureResource::collection($uoms)
                ->response();
        }

        $uoms = $this->uomService->getPaginated($perPage);

        return UnitOfMeasureResource::collection($uoms)
            ->response();
    }

    /**
     * Get all active units of measure.
     */
    public function active(): JsonResponse
    {
        $uoms = $this->uomService->getAllActive();

        return UnitOfMeasureResource::collection($uoms)
            ->response();
    }

    /**
     * Get all base units.
     */
    public function baseUnits(): JsonResponse
    {
        $uoms = $this->uomService->getBaseUnits();

        return UnitOfMeasureResource::collection($uoms)
            ->response();
    }

    /**
     * Store a newly created unit of measure.
     */
    public function store(StoreUnitOfMeasureRequest $request): JsonResponse
    {
        try {
            $uom = $this->uomService->create($request->validated());

            return (new UnitOfMeasureResource($uom))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create unit of measure',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified unit of measure.
     */
    public function show(string $id): JsonResponse
    {
        $uom = $this->uomService->findById($id);

        if (!$uom) {
            return response()->json([
                'message' => 'Unit of Measure not found',
            ], 404);
        }

        return (new UnitOfMeasureResource($uom))
            ->response();
    }

    /**
     * Update the specified unit of measure.
     */
    public function update(UpdateUnitOfMeasureRequest $request, string $id): JsonResponse
    {
        try {
            $uom = $this->uomService->update($id, $request->validated());

            return (new UnitOfMeasureResource($uom))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update unit of measure',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified unit of measure.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->uomService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete unit of measure',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Convert quantity between units of measure.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_uom_id' => 'required|uuid|exists:unit_of_measures,id',
            'to_uom_id' => 'required|uuid|exists:unit_of_measures,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        try {
            $convertedQuantity = $this->uomService->convertQuantity(
                $validated['from_uom_id'],
                $validated['to_uom_id'],
                $validated['quantity']
            );

            return response()->json([
                'from_uom_id' => $validated['from_uom_id'],
                'to_uom_id' => $validated['to_uom_id'],
                'original_quantity' => $validated['quantity'],
                'converted_quantity' => $convertedQuantity,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to convert quantity',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
