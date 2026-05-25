<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\CreateTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\DeleteTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\GetTraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\ListTraceLogsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\UpdateTraceLogServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListTraceLogRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertTraceLogRequest;
use Modules\Inventory\Presentation\Http\Resources\TraceLogResource;

final class TraceLogController extends Controller
{
    public function __construct(
        private readonly ListTraceLogsServiceInterface $listService,
        private readonly GetTraceLogServiceInterface $getService,
        private readonly CreateTraceLogServiceInterface $createService,
        private readonly UpdateTraceLogServiceInterface $updateService,
        private readonly DeleteTraceLogServiceInterface $deleteService,
    ) {
    }

    public function index(ListTraceLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => TraceLogResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|TraceLogResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TraceLogResource($result->valueOrFail());
    }

    public function store(UpsertTraceLogRequest $request): JsonResponse|TraceLogResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TraceLogResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTraceLogRequest $request, int|string $id): JsonResponse|TraceLogResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TraceLogResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}