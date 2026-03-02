<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Wms\Application\Commands\BeginCycleCountProgressCommand;
use Modules\Wms\Application\Commands\CompleteCycleCountCommand;
use Modules\Wms\Application\Commands\DeleteCycleCountCommand;
use Modules\Wms\Application\Commands\RecordCycleCountLineCommand;
use Modules\Wms\Application\Commands\StartCycleCountCommand;
use Modules\Wms\Application\Services\CycleCountService;
use Modules\Wms\Interfaces\Http\Requests\RecordCycleCountLineRequest;
use Modules\Wms\Interfaces\Http\Requests\StartCycleCountRequest;
use Modules\Wms\Interfaces\Http\Resources\CycleCountLineResource;
use Modules\Wms\Interfaces\Http\Resources\CycleCountResource;

class CycleCountController extends BaseController
{
    public function __construct(
        private readonly CycleCountService $service,
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
                fn ($cc) => (new CycleCountResource($cc))->resolve(),
                $result['items']
            ),
            message: 'Cycle counts retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(StartCycleCountRequest $request): JsonResponse
    {
        try {
            $cycleCount = $this->service->startCycleCount(new StartCycleCountCommand(
                tenantId: $request->validated('tenant_id'),
                warehouseId: $request->validated('warehouse_id'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CycleCountResource($cycleCount))->resolve(),
            message: 'Cycle count created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $cycleCount = $this->service->findById($id, $tenantId);

        if ($cycleCount === null) {
            return $this->error('Cycle count not found', status: 404);
        }

        return $this->success(
            data: (new CycleCountResource($cycleCount))->resolve(),
            message: 'Cycle count retrieved successfully',
        );
    }

    public function start(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $cycleCount = $this->service->beginProgress(new BeginCycleCountProgressCommand(
                id: $id,
                tenantId: $tenantId,
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CycleCountResource($cycleCount))->resolve(),
            message: 'Cycle count started successfully',
        );
    }

    public function recordLine(RecordCycleCountLineRequest $request, int $id): JsonResponse
    {
        try {
            $line = $this->service->recordLine(new RecordCycleCountLineCommand(
                cycleCountId: $id,
                tenantId: $request->validated('tenant_id'),
                productId: $request->validated('product_id'),
                binId: $request->validated('bin_id') !== null
                    ? (int) $request->validated('bin_id')
                    : null,
                systemQty: (string) $request->validated('system_qty'),
                countedQty: (string) $request->validated('counted_qty'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CycleCountLineResource($line))->resolve(),
            message: 'Cycle count line recorded successfully',
            status: 201,
        );
    }

    public function complete(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $cycleCount = $this->service->completeCycleCount(new CompleteCycleCountCommand(
                id: $id,
                tenantId: $tenantId,
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CycleCountResource($cycleCount))->resolve(),
            message: 'Cycle count completed successfully',
        );
    }

    public function lines(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $lines = $this->service->findLines($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($line) => (new CycleCountLineResource($line))->resolve(),
                $lines
            ),
            message: 'Cycle count lines retrieved successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteCycleCount(new DeleteCycleCountCommand($id, $tenantId));

            return $this->success(message: 'Cycle count deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }
}
