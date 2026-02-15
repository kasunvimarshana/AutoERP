<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\BaseController;
use App\Modules\Warehouse\Services\WarehouseTransferService;
use App\Http\Requests\Warehouse\StoreWarehouseTransferRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseTransferRequest;
use App\Http\Resources\Warehouse\WarehouseTransferResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseTransferController extends BaseController
{
    protected WarehouseTransferService $transferService;

    public function __construct(WarehouseTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'transfer_number',
                'status',
                'priority',
                'source_branch_id',
                'destination_branch_id',
                'from_date',
                'to_date',
            ]);

            if (!empty($filters)) {
                $transfers = $this->transferService->search($filters);
                return $this->successResponse(
                    WarehouseTransferResource::collection($transfers),
                    'Warehouse transfers retrieved successfully'
                );
            }

            $perPage = $request->input('per_page', 15);
            $transfers = $this->transferService->getAll([], $perPage);

            return $this->paginatedResponse(
                WarehouseTransferResource::collection($transfers),
                'Warehouse transfers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse transfers: ' . $e->getMessage());
        }
    }

    public function store(StoreWarehouseTransferRequest $request): JsonResponse
    {
        try {
            $transfer = $this->transferService->create($request->validated());

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create warehouse transfer: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->getById($id);

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse transfer: ' . $e->getMessage(), [], 404);
        }
    }

    public function update(UpdateWarehouseTransferRequest $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->update($id, $request->validated());

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update warehouse transfer: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->transferService->delete($id);

            return $this->successResponse(null, 'Warehouse transfer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete warehouse transfer: ' . $e->getMessage());
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->approve($id);

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer approved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve warehouse transfer: ' . $e->getMessage());
        }
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->only(['tracking_number', 'carrier']);
            $transfer = $this->transferService->ship($id, $data);

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer shipped successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to ship warehouse transfer: ' . $e->getMessage());
        }
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        try {
            $itemQuantities = $request->input('item_quantities', []);
            $transfer = $this->transferService->receive($id, $itemQuantities);

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer received successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to receive warehouse transfer: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string',
            ]);

            $transfer = $this->transferService->cancel($id, $request->input('cancellation_reason'));

            return $this->successResponse(
                new WarehouseTransferResource($transfer),
                'Warehouse transfer cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel warehouse transfer: ' . $e->getMessage());
        }
    }

    public function pending(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $transfers = $this->transferService->getPendingTransfers($branchId);

            return $this->successResponse(
                WarehouseTransferResource::collection($transfers),
                'Pending transfers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve pending transfers: ' . $e->getMessage());
        }
    }

    public function inTransit(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $transfers = $this->transferService->getInTransitTransfers($branchId);

            return $this->successResponse(
                WarehouseTransferResource::collection($transfers),
                'In-transit transfers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve in-transit transfers: ' . $e->getMessage());
        }
    }
}
