<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Application\DTOs\CycleCountData;
use Modules\Inventory\Infrastructure\Http\Resources\CycleCountResource;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountModel;

class CycleCountController extends BaseController
{
    public function __construct(CycleCountServiceInterface $service)
    {
        parent::__construct($service, CycleCountResource::class, CycleCountData::class);
    }

    protected function getModelClass(): string
    {
        return CycleCountModel::class;
    }

    /**
     * Create a new cycle count.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var CycleCountServiceInterface $service */
        $service = $this->service;
        $cycleCount = $service->createCycleCount($request->all());

        return (new CycleCountResource($cycleCount))->response()->setStatusCode(201);
    }

    /**
     * Submit counted quantities for a cycle count.
     */
    public function submit(Request $request, string $cycleCountId): JsonResponse
    {
        /** @var CycleCountServiceInterface $service */
        $service = $this->service;
        $cycleCount = $service->submitCount($cycleCountId, $request->input('lines', []));

        return (new CycleCountResource($cycleCount))->response();
    }

    /**
     * Approve a submitted cycle count, posting variance adjustments.
     */
    public function approve(Request $request, string $cycleCountId): JsonResponse
    {
        /** @var CycleCountServiceInterface $service */
        $service = $this->service;
        $cycleCount = $service->approve($cycleCountId);

        return (new CycleCountResource($cycleCount))->response();
    }

    /**
     * Cancel a draft cycle count.
     */
    public function cancel(Request $request, string $cycleCountId): JsonResponse
    {
        /** @var CycleCountServiceInterface $service */
        $service = $this->service;
        $cycleCount = $service->cancel($cycleCountId);

        return (new CycleCountResource($cycleCount))->response();
    }
}
