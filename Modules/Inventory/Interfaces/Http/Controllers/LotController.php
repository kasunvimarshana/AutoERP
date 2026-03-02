<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Commands\CreateLotCommand;
use Modules\Inventory\Application\Commands\UpdateLotCommand;
use Modules\Inventory\Application\Services\LotService;
use Modules\Inventory\Interfaces\Http\Requests\CreateLotRequest;
use Modules\Inventory\Interfaces\Http\Requests\UpdateLotRequest;
use Modules\Inventory\Interfaces\Http\Resources\LotResource;

class LotController extends BaseController
{
    public function __construct(
        private readonly LotService $lotService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $productId = request('product_id') ? (int) request('product_id') : null;
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->lotService->listLots($tenantId, $productId, $warehouseId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($lot) => (new LotResource($lot))->resolve(),
                $result['items']
            ),
            message: 'Inventory lots retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateLotRequest $request): JsonResponse
    {
        try {
            $lot = $this->lotService->createLot(new CreateLotCommand(
                tenantId: (int) $request->validated('tenant_id'),
                productId: (int) $request->validated('product_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                lotNumber: $request->validated('lot_number'),
                serialNumber: $request->validated('serial_number'),
                batchNumber: $request->validated('batch_number'),
                manufacturedDate: $request->validated('manufactured_date'),
                expiryDate: $request->validated('expiry_date'),
                quantity: (string) $request->validated('quantity'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new LotResource($lot))->resolve(),
                message: 'Inventory lot created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $lot = $this->lotService->getLot($tenantId, $id);

        if ($lot === null) {
            return $this->error('Inventory lot not found', status: 404);
        }

        return $this->success(
            data: (new LotResource($lot))->resolve(),
            message: 'Inventory lot retrieved successfully',
        );
    }

    public function update(UpdateLotRequest $request, int $id): JsonResponse
    {
        try {
            $lot = $this->lotService->updateLot(new UpdateLotCommand(
                tenantId: (int) $request->validated('tenant_id'),
                id: $id,
                lotNumber: $request->validated('lot_number'),
                serialNumber: $request->validated('serial_number'),
                batchNumber: $request->validated('batch_number'),
                manufacturedDate: $request->validated('manufactured_date'),
                expiryDate: $request->validated('expiry_date'),
                quantity: (string) $request->validated('quantity'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new LotResource($lot))->resolve(),
                message: 'Inventory lot updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tenantId = (int) request('tenant_id');
            $this->lotService->deleteLot($tenantId, $id);

            return $this->success(data: null, message: 'Inventory lot deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
