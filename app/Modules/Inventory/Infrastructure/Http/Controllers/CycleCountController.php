<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Infrastructure\Http\Resources\CycleCountLineResource;
use Modules\Inventory\Infrastructure\Http\Resources\CycleCountResource;

class CycleCountController extends Controller
{
    public function __construct(
        private readonly CycleCountServiceInterface $cycleCountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $counts = $this->cycleCountService->getAllCycleCounts($tenantId);
        return response()->json(CycleCountResource::collection($counts));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->createCycleCount($tenantId, $request->all());
        return response()->json(new CycleCountResource($count), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->getCycleCount($tenantId, $id);
        return response()->json(new CycleCountResource($count));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->updateCycleCount($tenantId, $id, $request->all());
        return response()->json(new CycleCountResource($count));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->cycleCountService->deleteCycleCount($tenantId, $id);
        return response()->json(null, 204);
    }

    public function start(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->startCycleCount($tenantId, $id);
        return response()->json(new CycleCountResource($count));
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->completeCycleCount($tenantId, $id);
        return response()->json(new CycleCountResource($count));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $count = $this->cycleCountService->cancelCycleCount($tenantId, $id);
        return response()->json(new CycleCountResource($count));
    }

    public function addLine(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->cycleCountService->addCycleCountLine($tenantId, $id, $request->all());
        return response()->json(new CycleCountLineResource($line), 201);
    }
}
