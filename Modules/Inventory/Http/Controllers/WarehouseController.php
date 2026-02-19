<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Inventory\Events\WarehouseActivated;
use Modules\Inventory\Events\WarehouseCreated;
use Modules\Inventory\Events\WarehouseDeactivated;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Http\Resources\WarehouseResource;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\WarehouseService;

/**
 * Warehouse Controller
 *
 * Handles HTTP requests for warehouse management including CRUD operations,
 * activation/deactivation, and setting default warehouse.
 */
class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $tenantId = $request->user()->currentTenant()->id;
        
        $filters = [
            'status' => $request->get('status'),
            'organization_id' => $request->get('organization_id'),
            'is_default' => $request->has('is_default') ? filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN) : null,
            'search' => $request->get('search'),
        ];

        $perPage = $request->get('per_page', 15);
        $warehouses = $this->warehouseService->getPaginatedWarehouses($tenantId, $filters, $perPage);

        return ApiResponse::paginated(
            $warehouses->setCollection(
                $warehouses->getCollection()->map(fn ($warehouse) => new WarehouseResource($warehouse))
            ),
            'Warehouses retrieved successfully'
        );
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $warehouse = DB::transaction(function () use ($data) {
            $warehouse = $this->warehouseService->create($data);
            event(new WarehouseCreated($warehouse));

            return $warehouse;
        });

        $warehouse->load(['organization']);

        return ApiResponse::created(
            new WarehouseResource($warehouse),
            'Warehouse created successfully'
        );
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        $warehouse->load(['organization', 'locations', 'stockItems.product']);

        return ApiResponse::success(
            new WarehouseResource($warehouse),
            'Warehouse retrieved successfully'
        );
    }

    /**
     * Update the specified warehouse.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $data = $request->validated();

        $warehouse = DB::transaction(function () use ($warehouse, $data) {
            return $this->warehouseService->update($warehouse->id, $data);
        });

        $warehouse->load(['organization']);

        return ApiResponse::success(
            new WarehouseResource($warehouse),
            'Warehouse updated successfully'
        );
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        DB::transaction(function () use ($warehouse) {
            $this->warehouseService->delete($warehouse->id);
        });

        return ApiResponse::success(
            null,
            'Warehouse deleted successfully'
        );
    }

    /**
     * Activate the warehouse.
     */
    public function activate(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('activate', $warehouse);

        if ($warehouse->status->isActive()) {
            return ApiResponse::error(
                'Warehouse is already active',
                422
            );
        }

        $warehouse = DB::transaction(function () use ($warehouse) {
            $warehouse = $this->warehouseService->activate($warehouse->id);
            event(new WarehouseActivated($warehouse));

            return $warehouse;
        });

        $warehouse->load(['organization']);

        return ApiResponse::success(
            new WarehouseResource($warehouse),
            'Warehouse activated successfully'
        );
    }

    /**
     * Deactivate the warehouse.
     */
    public function deactivate(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('deactivate', $warehouse);

        if (! $warehouse->status->isActive()) {
            return ApiResponse::error(
                'Warehouse is not active',
                422
            );
        }

        $warehouse = DB::transaction(function () use ($warehouse) {
            $warehouse = $this->warehouseService->deactivate($warehouse->id);
            event(new WarehouseDeactivated($warehouse));

            return $warehouse;
        });

        $warehouse->load(['organization']);

        return ApiResponse::success(
            new WarehouseResource($warehouse),
            'Warehouse deactivated successfully'
        );
    }

    /**
     * Set warehouse as default.
     */
    public function setDefault(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        if ($warehouse->is_default) {
            return ApiResponse::error(
                'Warehouse is already set as default',
                422
            );
        }

        $warehouse = DB::transaction(function () use ($warehouse) {
            return $this->warehouseService->setDefault($warehouse->id);
        });

        $warehouse->load(['organization']);

        return ApiResponse::success(
            new WarehouseResource($warehouse),
            'Warehouse set as default successfully'
        );
    }
}
