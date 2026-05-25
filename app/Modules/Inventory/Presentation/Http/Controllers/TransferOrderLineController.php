<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\CreateTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\DeleteTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\GetTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\ListTransferOrderLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\UpdateTransferOrderLineServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListTransferOrderLineRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertTransferOrderLineRequest;
use Modules\Inventory\Presentation\Http\Resources\TransferOrderLineResource;

final class TransferOrderLineController extends Controller
{
    public function __construct(
        private readonly ListTransferOrderLinesServiceInterface $listService,
        private readonly GetTransferOrderLineServiceInterface $getService,
        private readonly CreateTransferOrderLineServiceInterface $createService,
        private readonly UpdateTransferOrderLineServiceInterface $updateService,
        private readonly DeleteTransferOrderLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListTransferOrderLineRequest $request): JsonResponse
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
            'data' => TransferOrderLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|TransferOrderLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TransferOrderLineResource($result->valueOrFail());
    }

    public function store(UpsertTransferOrderLineRequest $request): JsonResponse|TransferOrderLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TransferOrderLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTransferOrderLineRequest $request, int|string $id): JsonResponse|TransferOrderLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TransferOrderLineResource($result->valueOrFail());
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