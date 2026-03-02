<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Wms\Application\Commands\CreateBinCommand;
use Modules\Wms\Application\Commands\DeleteBinCommand;
use Modules\Wms\Application\Commands\UpdateBinCommand;
use Modules\Wms\Application\Services\BinService;
use Modules\Wms\Interfaces\Http\Requests\CreateBinRequest;
use Modules\Wms\Interfaces\Http\Requests\UpdateBinRequest;
use Modules\Wms\Interfaces\Http\Resources\BinResource;

class BinController extends BaseController
{
    public function __construct(
        private readonly BinService $service,
    ) {}

    public function store(CreateBinRequest $request): JsonResponse
    {
        try {
            $bin = $this->service->createBin(new CreateBinCommand(
                tenantId: $request->validated('tenant_id'),
                aisleId: $request->validated('aisle_id'),
                code: $request->validated('code'),
                description: $request->validated('description'),
                maxCapacity: $request->validated('max_capacity') !== null
                    ? (int) $request->validated('max_capacity')
                    : null,
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new BinResource($bin))->resolve(),
            message: 'Bin created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $bin = $this->service->findById($id, $tenantId);

        if ($bin === null) {
            return $this->error('Bin not found', status: 404);
        }

        return $this->success(
            data: (new BinResource($bin))->resolve(),
            message: 'Bin retrieved successfully',
        );
    }

    public function update(UpdateBinRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $bin = $this->service->updateBin(new UpdateBinCommand(
                id: $id,
                tenantId: $tenantId,
                description: $request->validated('description'),
                maxCapacity: $request->validated('max_capacity') !== null
                    ? (int) $request->validated('max_capacity')
                    : null,
                isActive: (bool) ($request->validated('is_active') ?? true),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new BinResource($bin))->resolve(),
            message: 'Bin updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteBin(new DeleteBinCommand($id, $tenantId));

            return $this->success(message: 'Bin deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
