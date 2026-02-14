<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Api\ApiController;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitController extends ApiController
{
    protected UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * Display a listing of units.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        
        $units = $type 
            ? $this->unitService->getUnitsByType($type)
            : Unit::active()->get();

        return $this->success($units, 'Units retrieved successfully');
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:units,code',
            'symbol' => 'nullable|string|max:20',
            'type' => 'required|in:basic,mass,length,volume,area,time,temperature,quantity',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;

        $unit = $this->unitService->createUnit($validated);

        return $this->success($unit, 'Unit created successfully', 201);
    }

    /**
     * Display the specified unit.
     */
    public function show(int $id): JsonResponse
    {
        $unit = Unit::with('baseUnit', 'derivedUnits')->findOrFail($id);
        
        return $this->success($unit, 'Unit retrieved successfully');
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'symbol' => 'nullable|string|max:20',
            'type' => 'sometimes|in:basic,mass,length,volume,area,time,temperature,quantity',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $unit = $this->unitService->updateUnit($id, $validated);

        return $this->success($unit, 'Unit updated successfully');
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->unitService->deleteUnit($id);
        
        return $this->success(null, 'Unit deleted successfully');
    }

    /**
     * Convert value between units.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'from_unit_id' => 'required|exists:units,id',
            'to_unit_id' => 'required|exists:units,id',
        ]);

        try {
            $convertedValue = $this->unitService->convertUnits(
                $validated['value'],
                $validated['from_unit_id'],
                $validated['to_unit_id']
            );

            return $this->success([
                'original_value' => $validated['value'],
                'converted_value' => $convertedValue,
            ], 'Conversion completed successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
