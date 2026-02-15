<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\BaseController;
use App\Modules\Warehouse\Services\WarehousePickingService;
use App\Http\Requests\Warehouse\StoreWarehousePickingRequest;
use App\Http\Resources\Warehouse\WarehousePickingResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehousePickingController extends BaseController
{
    protected WarehousePickingService $pickingService;

    public function __construct(WarehousePickingService $pickingService)
    {
        $this->pickingService = $pickingService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'picking_number',
                'status',
                'priority',
                'picking_type',
                'branch_id',
                'assigned_to',
                'from_date',
                'to_date',
            ]);

            if (!empty($filters)) {
                $pickings = $this->pickingService->search($filters);
                return $this->successResponse(
                    WarehousePickingResource::collection($pickings),
                    'Warehouse pickings retrieved successfully'
                );
            }

            $perPage = $request->input('per_page', 15);
            $pickings = $this->pickingService->getAll([], $perPage);

            return $this->paginatedResponse(
                WarehousePickingResource::collection($pickings),
                'Warehouse pickings retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse pickings: ' . $e->getMessage());
        }
    }

    public function store(StoreWarehousePickingRequest $request): JsonResponse
    {
        try {
            $picking = $this->pickingService->create($request->validated());

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Warehouse picking created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create warehouse picking: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $picking = $this->pickingService->getById($id);

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Warehouse picking retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse picking: ' . $e->getMessage(), [], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->pickingService->delete($id);

            return $this->successResponse(null, 'Warehouse picking deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete warehouse picking: ' . $e->getMessage());
        }
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $picking = $this->pickingService->assign($id, $request->input('user_id'));

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Picking assigned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign picking: ' . $e->getMessage());
        }
    }

    public function start(int $id): JsonResponse
    {
        try {
            $picking = $this->pickingService->start($id);

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Picking started successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to start picking: ' . $e->getMessage());
        }
    }

    public function pick(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'item_quantities' => 'required|array',
                'item_quantities.*' => 'required|numeric|min:0',
            ]);

            $picking = $this->pickingService->pick($id, $request->input('item_quantities'));

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Items picked successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to pick items: ' . $e->getMessage());
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $picking = $this->pickingService->complete($id);

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Picking completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete picking: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string',
            ]);

            $picking = $this->pickingService->cancel($id, $request->input('cancellation_reason'));

            return $this->successResponse(
                new WarehousePickingResource($picking),
                'Picking cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel picking: ' . $e->getMessage());
        }
    }

    public function pending(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $pickings = $this->pickingService->getPendingPickings($branchId);

            return $this->successResponse(
                WarehousePickingResource::collection($pickings),
                'Pending pickings retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve pending pickings: ' . $e->getMessage());
        }
    }

    public function efficiency(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $efficiency = $this->pickingService->getPickingEfficiency(
                $request->input('user_id'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->successResponse(
                $efficiency,
                'Picking efficiency retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve picking efficiency: ' . $e->getMessage());
        }
    }
}
