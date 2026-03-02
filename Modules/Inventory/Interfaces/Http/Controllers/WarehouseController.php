<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Commands\CreateWarehouseCommand;
use Modules\Inventory\Application\Services\WarehouseService;
use Modules\Inventory\Interfaces\Http\Requests\CreateWarehouseRequest;
use Modules\Inventory\Interfaces\Http\Resources\WarehouseResource;

class WarehouseController extends BaseController
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->warehouseService->listWarehouses($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($warehouse) => (new WarehouseResource($warehouse))->resolve(),
                $result['items']
            ),
            message: 'Warehouses retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateWarehouseRequest $request): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->createWarehouse(new CreateWarehouseCommand(
                tenantId: (int) $request->validated('tenant_id'),
                code: $request->validated('code'),
                name: $request->validated('name'),
                address: $request->validated('address'),
                status: $request->validated('status', 'active'),
            ));

            return $this->success(
                data: (new WarehouseResource($warehouse))->resolve(),
                message: 'Warehouse created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouse = $this->warehouseService->findWarehouseById($id, $tenantId);

        if ($warehouse === null) {
            return $this->error('Warehouse not found', status: 404);
        }

        return $this->success(
            data: (new WarehouseResource($warehouse))->resolve(),
            message: 'Warehouse retrieved successfully',
        );
    }
}
