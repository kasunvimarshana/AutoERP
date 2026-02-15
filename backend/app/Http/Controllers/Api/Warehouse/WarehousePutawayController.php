<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\BaseController;
use App\Modules\Warehouse\Services\WarehousePutawayService;
use App\Http\Requests\Warehouse\StoreWarehousePutawayRequest;
use App\Http\Resources\Warehouse\WarehousePutawayResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehousePutawayController extends BaseController
{
    protected WarehousePutawayService $putawayService;

    public function __construct(WarehousePutawayService $putawayService)
    {
        $this->putawayService = $putawayService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'putaway_number',
                'status',
                'priority',
                'putaway_type',
                'branch_id',
                'assigned_to',
                'from_date',
                'to_date',
            ]);

            if (!empty($filters)) {
                $putaways = $this->putawayService->search($filters);
                return $this->successResponse(
                    WarehousePutawayResource::collection($putaways),
                    'Warehouse putaways retrieved successfully'
                );
            }

            $perPage = $request->input('per_page', 15);
            $putaways = $this->putawayService->getAll([], $perPage);

            return $this->paginatedResponse(
                WarehousePutawayResource::collection($putaways),
                'Warehouse putaways retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse putaways: ' . $e->getMessage());
        }
    }

    public function store(StoreWarehousePutawayRequest $request): JsonResponse
    {
        try {
            $putaway = $this->putawayService->create($request->validated());

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Warehouse putaway created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create warehouse putaway: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $putaway = $this->putawayService->getById($id);

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Warehouse putaway retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse putaway: ' . $e->getMessage(), [], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->putawayService->delete($id);

            return $this->successResponse(null, 'Warehouse putaway deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete warehouse putaway: ' . $e->getMessage());
        }
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $putaway = $this->putawayService->assign($id, $request->input('user_id'));

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Putaway assigned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign putaway: ' . $e->getMessage());
        }
    }

    public function start(int $id): JsonResponse
    {
        try {
            $putaway = $this->putawayService->start($id);

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Putaway started successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to start putaway: ' . $e->getMessage());
        }
    }

    public function putaway(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'item_quantities' => 'required|array',
                'item_quantities.*' => 'required|numeric|min:0',
            ]);

            $putaway = $this->putawayService->putaway($id, $request->input('item_quantities'));

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Items put away successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to putaway items: ' . $e->getMessage());
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $putaway = $this->putawayService->complete($id);

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Putaway completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete putaway: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string',
            ]);

            $putaway = $this->putawayService->cancel($id, $request->input('cancellation_reason'));

            return $this->successResponse(
                new WarehousePutawayResource($putaway),
                'Putaway cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel putaway: ' . $e->getMessage());
        }
    }

    public function pending(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $putaways = $this->putawayService->getPendingPutaways($branchId);

            return $this->successResponse(
                WarehousePutawayResource::collection($putaways),
                'Pending putaways retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve pending putaways: ' . $e->getMessage());
        }
    }
}
