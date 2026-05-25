<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\CreateSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\DeleteSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\GetSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\ListSupplierItemsServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\UpdateSupplierItemServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierItemRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierItemRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierItemResource;

final class SupplierItemController extends Controller
{
    public function __construct(
        private readonly ListSupplierItemsServiceInterface $listService,
        private readonly GetSupplierItemServiceInterface $getService,
        private readonly CreateSupplierItemServiceInterface $createService,
        private readonly UpdateSupplierItemServiceInterface $updateService,
        private readonly DeleteSupplierItemServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierItemRequest $request): JsonResponse
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
            'data' => SupplierItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierItemResource($result->valueOrFail());
    }

    public function store(UpsertSupplierItemRequest $request): JsonResponse|SupplierItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierItemRequest $request, int|string $id): JsonResponse|SupplierItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierItemResource($result->valueOrFail());
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