<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\CreateReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\DeleteReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\GetReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\ListReceiptInspectionsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\UpdateReceiptInspectionServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListReceiptInspectionRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertReceiptInspectionRequest;
use Modules\Inventory\Presentation\Http\Resources\ReceiptInspectionResource;

final class ReceiptInspectionController extends Controller
{
    public function __construct(
        private readonly ListReceiptInspectionsServiceInterface $listService,
        private readonly GetReceiptInspectionServiceInterface $getService,
        private readonly CreateReceiptInspectionServiceInterface $createService,
        private readonly UpdateReceiptInspectionServiceInterface $updateService,
        private readonly DeleteReceiptInspectionServiceInterface $deleteService,
    ) {
    }

    public function index(ListReceiptInspectionRequest $request): JsonResponse
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
            'data' => ReceiptInspectionResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ReceiptInspectionResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ReceiptInspectionResource($result->valueOrFail());
    }

    public function store(UpsertReceiptInspectionRequest $request): JsonResponse|ReceiptInspectionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ReceiptInspectionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertReceiptInspectionRequest $request, int|string $id): JsonResponse|ReceiptInspectionResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ReceiptInspectionResource($result->valueOrFail());
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