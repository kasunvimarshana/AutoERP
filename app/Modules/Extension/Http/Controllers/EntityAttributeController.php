<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Extension\Http\Requests\ListEntityAttributeRequest;
use Modules\Extension\Http\Requests\UpsertEntityAttributeRequest;
use Modules\Extension\Http\Resources\EntityAttributeResource;
use Modules\Extension\Services\EntityAttributes\CreateEntityAttributeService;
use Modules\Extension\Services\EntityAttributes\DeleteEntityAttributeService;
use Modules\Extension\Services\EntityAttributes\GetEntityAttributeService;
use Modules\Extension\Services\EntityAttributes\ListEntityAttributesService;
use Modules\Extension\Services\EntityAttributes\UpdateEntityAttributeService;

final class EntityAttributeController extends Controller
{
    public function __construct(
        private readonly ListEntityAttributesService $listService,
        private readonly GetEntityAttributeService $getService,
        private readonly CreateEntityAttributeService $createService,
        private readonly UpdateEntityAttributeService $updateService,
        private readonly DeleteEntityAttributeService $deleteService,
    ) {}

    public function index(ListEntityAttributeRequest $request): JsonResponse
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
            'data' => EntityAttributeResource::collection($pageResult->items)->resolve(),
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(int|string $id): JsonResponse|EntityAttributeResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EntityAttributeResource($result->valueOrFail());
    }

    public function store(UpsertEntityAttributeRequest $request): JsonResponse|EntityAttributeResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EntityAttributeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEntityAttributeRequest $request, int|string $id): JsonResponse|EntityAttributeResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'EXTENSION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EntityAttributeResource($result->valueOrFail());
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
