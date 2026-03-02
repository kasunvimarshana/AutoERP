<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Wms\Application\Commands\CreateZoneCommand;
use Modules\Wms\Application\Commands\DeleteZoneCommand;
use Modules\Wms\Application\Commands\UpdateZoneCommand;
use Modules\Wms\Application\Services\AisleService;
use Modules\Wms\Application\Services\ZoneService;
use Modules\Wms\Interfaces\Http\Requests\CreateZoneRequest;
use Modules\Wms\Interfaces\Http\Requests\UpdateZoneRequest;
use Modules\Wms\Interfaces\Http\Resources\AisleResource;
use Modules\Wms\Interfaces\Http\Resources\ZoneResource;

class ZoneController extends BaseController
{
    public function __construct(
        private readonly ZoneService $service,
        private readonly AisleService $aisleService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = (int) request('warehouse_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $warehouseId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($zone) => (new ZoneResource($zone))->resolve(),
                $result['items']
            ),
            message: 'Zones retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateZoneRequest $request): JsonResponse
    {
        try {
            $zone = $this->service->createZone(new CreateZoneCommand(
                tenantId: $request->validated('tenant_id'),
                warehouseId: $request->validated('warehouse_id'),
                name: $request->validated('name'),
                code: $request->validated('code'),
                description: $request->validated('description'),
                sortOrder: (int) ($request->validated('sort_order') ?? 0),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new ZoneResource($zone))->resolve(),
            message: 'Zone created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $zone = $this->service->findById($id, $tenantId);

        if ($zone === null) {
            return $this->error('Zone not found', status: 404);
        }

        return $this->success(
            data: (new ZoneResource($zone))->resolve(),
            message: 'Zone retrieved successfully',
        );
    }

    public function update(UpdateZoneRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $zone = $this->service->updateZone(new UpdateZoneCommand(
                id: $id,
                tenantId: $tenantId,
                name: $request->validated('name'),
                description: $request->validated('description'),
                sortOrder: (int) ($request->validated('sort_order') ?? 0),
                isActive: (bool) ($request->validated('is_active') ?? true),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new ZoneResource($zone))->resolve(),
            message: 'Zone updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteZone(new DeleteZoneCommand($id, $tenantId));

            return $this->success(message: 'Zone deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function aisles(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $aisles = $this->aisleService->findByZone($tenantId, $id);

        return $this->success(
            data: array_map(
                fn ($aisle) => (new AisleResource($aisle))->resolve(),
                $aisles
            ),
            message: 'Aisles retrieved successfully',
        );
    }
}
