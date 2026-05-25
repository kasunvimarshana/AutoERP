<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\CreateCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\DeleteCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\GetCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\ListCycleCountLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\UpdateCycleCountLineServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListCycleCountLineRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertCycleCountLineRequest;
use Modules\Inventory\Presentation\Http\Resources\CycleCountLineResource;

final class CycleCountLineController extends Controller
{
    public function __construct(
        private readonly ListCycleCountLinesServiceInterface $listService,
        private readonly GetCycleCountLineServiceInterface $getService,
        private readonly CreateCycleCountLineServiceInterface $createService,
        private readonly UpdateCycleCountLineServiceInterface $updateService,
        private readonly DeleteCycleCountLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListCycleCountLineRequest $request): JsonResponse
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
            'data' => CycleCountLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CycleCountLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CycleCountLineResource($result->valueOrFail());
    }

    public function store(UpsertCycleCountLineRequest $request): JsonResponse|CycleCountLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CycleCountLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCycleCountLineRequest $request, int|string $id): JsonResponse|CycleCountLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CycleCountLineResource($result->valueOrFail());
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