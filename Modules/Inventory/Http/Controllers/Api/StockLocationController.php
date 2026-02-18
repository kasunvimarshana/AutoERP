<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\StoreStockLocationRequest;
use Modules\Inventory\Http\Requests\UpdateStockLocationRequest;
use Modules\Inventory\Http\Resources\StockLocationResource;
use Modules\Inventory\Repositories\Contracts\StockLocationRepositoryInterface;

/**
 * Stock Location API Controller
 */
class StockLocationController extends Controller
{
    public function __construct(
        protected StockLocationRepositoryInterface $locationRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $locations = $this->locationRepository->paginate($perPage);

        return StockLocationResource::collection($locations)->response();
    }

    public function store(StoreStockLocationRequest $request): JsonResponse
    {
        $location = $this->locationRepository->create($request->validated());

        return (new StockLocationResource($location))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (! $location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        return (new StockLocationResource($location))->response();
    }

    public function update(UpdateStockLocationRequest $request, int $id): JsonResponse
    {
        $location = $this->locationRepository->update($id, $request->validated());

        return (new StockLocationResource($location))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->locationRepository->delete($id);

        if (! $deleted) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        return response()->json(['message' => 'Location deleted successfully'], 200);
    }

    public function byWarehouse(int $warehouseId): JsonResponse
    {
        $locations = $this->locationRepository->getByWarehouse($warehouseId);

        return StockLocationResource::collection($locations)->response();
    }
}
