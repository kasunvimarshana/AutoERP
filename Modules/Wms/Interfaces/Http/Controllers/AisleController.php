<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Wms\Application\Commands\CreateAisleCommand;
use Modules\Wms\Application\Commands\DeleteAisleCommand;
use Modules\Wms\Application\Commands\UpdateAisleCommand;
use Modules\Wms\Application\Services\AisleService;
use Modules\Wms\Application\Services\BinService;
use Modules\Wms\Interfaces\Http\Requests\CreateAisleRequest;
use Modules\Wms\Interfaces\Http\Requests\UpdateAisleRequest;
use Modules\Wms\Interfaces\Http\Resources\AisleResource;
use Modules\Wms\Interfaces\Http\Resources\BinResource;

class AisleController extends BaseController
{
    public function __construct(
        private readonly AisleService $service,
        private readonly BinService $binService,
    ) {}

    public function store(CreateAisleRequest $request): JsonResponse
    {
        try {
            $aisle = $this->service->createAisle(new CreateAisleCommand(
                tenantId: $request->validated('tenant_id'),
                zoneId: $request->validated('zone_id'),
                name: $request->validated('name'),
                code: $request->validated('code'),
                description: $request->validated('description'),
                sortOrder: (int) ($request->validated('sort_order') ?? 0),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new AisleResource($aisle))->resolve(),
            message: 'Aisle created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $aisle = $this->service->findById($id, $tenantId);

        if ($aisle === null) {
            return $this->error('Aisle not found', status: 404);
        }

        return $this->success(
            data: (new AisleResource($aisle))->resolve(),
            message: 'Aisle retrieved successfully',
        );
    }

    public function update(UpdateAisleRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $aisle = $this->service->updateAisle(new UpdateAisleCommand(
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
            data: (new AisleResource($aisle))->resolve(),
            message: 'Aisle updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteAisle(new DeleteAisleCommand($id, $tenantId));

            return $this->success(message: 'Aisle deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function bins(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $bins = $this->binService->findByAisle($tenantId, $id);

        return $this->success(
            data: array_map(
                fn ($bin) => (new BinResource($bin))->resolve(),
                $bins
            ),
            message: 'Bins retrieved successfully',
        );
    }
}
