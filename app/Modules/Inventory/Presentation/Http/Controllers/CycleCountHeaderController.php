<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\CreateCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\DeleteCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\GetCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\ListCycleCountHeadersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\UpdateCycleCountHeaderServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListCycleCountHeaderRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertCycleCountHeaderRequest;
use Modules\Inventory\Presentation\Http\Resources\CycleCountHeaderResource;

final class CycleCountHeaderController extends Controller
{
    public function __construct(
        private readonly ListCycleCountHeadersServiceInterface $listService,
        private readonly GetCycleCountHeaderServiceInterface $getService,
        private readonly CreateCycleCountHeaderServiceInterface $createService,
        private readonly UpdateCycleCountHeaderServiceInterface $updateService,
        private readonly DeleteCycleCountHeaderServiceInterface $deleteService,
    ) {
    }

    public function index(ListCycleCountHeaderRequest $request): JsonResponse
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
            'data' => CycleCountHeaderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CycleCountHeaderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CycleCountHeaderResource($result->valueOrFail());
    }

    public function store(UpsertCycleCountHeaderRequest $request): JsonResponse|CycleCountHeaderResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CycleCountHeaderResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCycleCountHeaderRequest $request, int|string $id): JsonResponse|CycleCountHeaderResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CycleCountHeaderResource($result->valueOrFail());
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