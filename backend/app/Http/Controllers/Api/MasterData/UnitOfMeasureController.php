<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UnitOfMeasureResource;
use App\Modules\MasterData\Repositories\UnitOfMeasureRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitOfMeasureController extends BaseController
{
    public function __construct(
        private readonly UnitOfMeasureRepository $unitRepository
    ) {}

    /**
     * Display a listing of units of measure.
     */
    public function index(): AnonymousResourceCollection
    {
        $units = $this->unitRepository->all();
        return UnitOfMeasureResource::collection($units);
    }

    /**
     * Store a newly created unit of measure.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:units_of_measure,code'],
            'type' => ['required', 'in:weight,length,volume,quantity,time,area'],
            'base_unit_id' => ['nullable', 'exists:units_of_measure,id'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $unit = $this->unitRepository->create($validated);

            return $this->successResponse(
                new UnitOfMeasureResource($unit),
                'Unit of measure created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create unit of measure',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified unit of measure.
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitRepository->find($id);

        if (!$unit) {
            return $this->errorResponse('Unit of measure not found', [], 404);
        }

        return $this->successResponse(new UnitOfMeasureResource($unit));
    }

    /**
     * Update the specified unit of measure.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $unit = $this->unitRepository->find($id);

        if (!$unit) {
            return $this->errorResponse('Unit of measure not found', [], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:units_of_measure,code,' . $id],
            'type' => ['sometimes', 'required', 'in:weight,length,volume,quantity,time,area'],
            'base_unit_id' => ['nullable', 'exists:units_of_measure,id'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $unit = $this->unitRepository->update($unit, $validated);

            return $this->successResponse(
                new UnitOfMeasureResource($unit),
                'Unit of measure updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update unit of measure',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified unit of measure.
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitRepository->find($id);

        if (!$unit) {
            return $this->errorResponse('Unit of measure not found', [], 404);
        }

        try {
            $this->unitRepository->delete($unit);

            return $this->successResponse(
                null,
                'Unit of measure deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete unit of measure',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Get units by type.
     */
    public function byType(string $type): AnonymousResourceCollection
    {
        $units = $this->unitRepository->getByType($type);
        return UnitOfMeasureResource::collection($units);
    }

    /**
     * Get base units only.
     */
    public function baseUnits(): AnonymousResourceCollection
    {
        $units = $this->unitRepository->getBaseUnits();
        return UnitOfMeasureResource::collection($units);
    }
}
