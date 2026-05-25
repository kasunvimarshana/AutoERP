<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ComboItems\CreateComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\DeleteComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\GetComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\ListComboItemsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\UpdateComboItemServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListComboItemRequest;
use Modules\Item\Presentation\Http\Requests\UpsertComboItemRequest;
use Modules\Item\Presentation\Http\Resources\ComboItemResource;

final class ComboItemController extends Controller
{
    public function __construct(
        private readonly ListComboItemsServiceInterface $listService,
        private readonly GetComboItemServiceInterface $getService,
        private readonly CreateComboItemServiceInterface $createService,
        private readonly UpdateComboItemServiceInterface $updateService,
        private readonly DeleteComboItemServiceInterface $deleteService,
    ) {
    }

    public function index(ListComboItemRequest $request): JsonResponse
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
            'data' => ComboItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ComboItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ComboItemResource($result->valueOrFail());
    }

    public function store(UpsertComboItemRequest $request): JsonResponse|ComboItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ComboItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertComboItemRequest $request, int|string $id): JsonResponse|ComboItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ComboItemResource($result->valueOrFail());
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
