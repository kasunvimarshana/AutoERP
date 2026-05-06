<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Inventory\Infrastructure\Http\Requests\CompleteCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ListCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StartCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Resources\CycleCountResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryCycleCountController
{
    public function __construct(
        private readonly CreateCycleCountServiceInterface $createCycleCountService,
        private readonly FindCycleCountServiceInterface $findCycleCountService,
        private readonly StartCycleCountServiceInterface $startCycleCountService,
        private readonly CompleteCycleCountServiceInterface $completeCycleCountService,
    ) {}

    public function index(ListCycleCountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $counts = $this->findCycleCountService->list(
            tenantId: (int) $validated['tenant_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($counts);
    }

    public function show(ListCycleCountRequest $request, int $cycleCount): JsonResponse
    {
        $validated = $request->validated();

        $count = $this->findCycleCountService->find((int) $validated['tenant_id'], $cycleCount);
        if ($count === null) {
            return response()->json(['message' => 'Cycle count not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return (new CycleCountResource($count))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreCycleCountRequest $request): JsonResponse
    {
        $count = $this->createCycleCountService->execute($request->validated());

        return (new CycleCountResource($count))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function start(StartCycleCountRequest $request, int $cycleCount): JsonResponse
    {
        $validated = $request->validated();
        $count = $this->startCycleCountService->execute((int) $validated['tenant_id'], $cycleCount);

        return (new CycleCountResource($count))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function complete(CompleteCycleCountRequest $request, int $cycleCount): JsonResponse
    {
        $validated = $request->validated();
        $count = $this->completeCycleCountService->execute(
            (int) $validated['tenant_id'],
            $cycleCount,
            (int) $validated['approved_by_user_id'],
            array_map(static fn (array $line): array => [
                'line_id' => (int) $line['line_id'],
                'counted_qty' => (string) $line['counted_qty'],
            ], $validated['lines']),
        );

        return (new CycleCountResource($count))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }
}
