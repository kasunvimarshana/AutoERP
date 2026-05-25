<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\GetSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\ListSuppliersServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\UpdateSupplierServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierResource;

final class SupplierController extends Controller
{
    public function __construct(
        private readonly ListSuppliersServiceInterface $listService,
        private readonly GetSupplierServiceInterface $getService,
        private readonly CreateSupplierServiceInterface $createService,
        private readonly UpdateSupplierServiceInterface $updateService,
        private readonly DeleteSupplierServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierRequest $request): JsonResponse
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
            'data' => SupplierResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierResource($result->valueOrFail());
    }

    public function store(UpsertSupplierRequest $request): JsonResponse|SupplierResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierRequest $request, int|string $id): JsonResponse|SupplierResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierResource($result->valueOrFail());
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