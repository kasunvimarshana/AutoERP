<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Http\Requests\StoreUnitConversionRequest;
use Modules\Product\Http\Requests\StoreUnitRequest;
use Modules\Product\Http\Requests\UpdateUnitRequest;
use Modules\Product\Http\Resources\ProductUnitConversionResource;
use Modules\Product\Http\Resources\UnitResource;
use Modules\Product\Models\ProductUnitConversion;
use Modules\Product\Models\Unit;

/**
 * Unit Controller
 */
class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $units = $query->paginate($perPage);

        return response()->json([
            'data' => UnitResource::collection($units->items()),
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    /**
     * Store a newly created unit.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $unit = DB::transaction(function () use ($data) {
            return Unit::create($data);
        });

        return response()->json([
            'message' => 'Unit created successfully.',
            'data' => new UnitResource($unit),
        ], 201);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return response()->json([
            'data' => new UnitResource($unit),
        ]);
    }

    /**
     * Update the specified unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($unit, $data) {
            $unit->update($data);
        });

        return response()->json([
            'message' => 'Unit updated successfully.',
            'data' => new UnitResource($unit),
        ]);
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $productCount = $unit->productsAsBuyingUnit()->count() + $unit->productsAsSellingUnit()->count();

        if ($productCount > 0) {
            return response()->json([
                'message' => 'Cannot delete unit that is used by products.',
            ], 400);
        }

        DB::transaction(function () use ($unit) {
            ProductUnitConversion::where('from_unit_id', $unit->id)
                ->orWhere('to_unit_id', $unit->id)
                ->delete();

            $unit->delete();
        });

        return response()->json([
            'message' => 'Unit deleted successfully.',
        ]);
    }

    /**
     * Add a conversion for the specified unit.
     */
    public function addConversion(StoreUnitConversionRequest $request, Unit $unit): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['from_unit_id'] = $unit->id;

        $conversion = DB::transaction(function () use ($data) {
            return ProductUnitConversion::create($data);
        });

        $conversion->load(['fromUnit', 'toUnit']);

        return response()->json([
            'message' => 'Unit conversion added successfully.',
            'data' => new ProductUnitConversionResource($conversion),
        ], 201);
    }

    /**
     * Get conversions for the specified unit.
     */
    public function getConversions(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        $conversions = ProductUnitConversion::where('from_unit_id', $unit->id)
            ->orWhere('to_unit_id', $unit->id)
            ->with(['fromUnit', 'toUnit'])
            ->get();

        return response()->json([
            'data' => ProductUnitConversionResource::collection($conversions),
        ]);
    }

    /**
     * Convert quantity between units.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'from_unit_id' => ['required', 'exists:units,id'],
            'to_unit_id' => ['required', 'exists:units,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $fromUnitId = $request->from_unit_id;
        $toUnitId = $request->to_unit_id;
        $quantity = $request->quantity;

        if ($fromUnitId === $toUnitId) {
            return response()->json([
                'from_unit_id' => $fromUnitId,
                'to_unit_id' => $toUnitId,
                'from_quantity' => $quantity,
                'to_quantity' => $quantity,
                'conversion_factor' => '1.0000000000',
            ]);
        }

        $conversion = ProductUnitConversion::where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();

        if (! $conversion) {
            $reverseConversion = ProductUnitConversion::where('from_unit_id', $toUnitId)
                ->where('to_unit_id', $fromUnitId)
                ->first();

            if ($reverseConversion) {
                $factor = bcdiv('1', $reverseConversion->conversion_factor, 10);
                $convertedQuantity = bcmul($quantity, $factor, 10);

                return response()->json([
                    'from_unit_id' => $fromUnitId,
                    'to_unit_id' => $toUnitId,
                    'from_quantity' => $quantity,
                    'to_quantity' => $convertedQuantity,
                    'conversion_factor' => $factor,
                ]);
            }

            return response()->json([
                'message' => 'No conversion found between these units.',
            ], 404);
        }

        $convertedQuantity = bcmul($quantity, $conversion->conversion_factor, 10);

        return response()->json([
            'from_unit_id' => $fromUnitId,
            'to_unit_id' => $toUnitId,
            'from_quantity' => $quantity,
            'to_quantity' => $convertedQuantity,
            'conversion_factor' => $conversion->conversion_factor,
        ]);
    }
}
