<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\Serials\CreateSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\DeleteSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\GetSerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\ListSerialsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\UpdateSerialServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListSerialRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertSerialRequest;
use Modules\Inventory\Presentation\Http\Resources\SerialResource;

final class SerialController extends Controller
{
    public function __construct(
        private readonly ListSerialsServiceInterface $listService,
        private readonly GetSerialServiceInterface $getService,
        private readonly CreateSerialServiceInterface $createService,
        private readonly UpdateSerialServiceInterface $updateService,
        private readonly DeleteSerialServiceInterface $deleteService,
    ) {
    }

    public function index(ListSerialRequest $request): JsonResponse
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
            'data' => SerialResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SerialResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SerialResource($result->valueOrFail());
    }

    public function store(UpsertSerialRequest $request): JsonResponse|SerialResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SerialResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSerialRequest $request, int|string $id): JsonResponse|SerialResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SerialResource($result->valueOrFail());
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