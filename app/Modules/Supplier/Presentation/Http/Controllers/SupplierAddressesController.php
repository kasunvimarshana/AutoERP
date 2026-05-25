<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\CreateSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\DeleteSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\GetSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\ListSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\UpdateSupplierAddressesServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierAddressesRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierAddressesRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierAddressesResource;

final class SupplierAddressesController extends Controller
{
    public function __construct(
        private readonly ListSupplierAddressesServiceInterface $listService,
        private readonly GetSupplierAddressesServiceInterface $getService,
        private readonly CreateSupplierAddressesServiceInterface $createService,
        private readonly UpdateSupplierAddressesServiceInterface $updateService,
        private readonly DeleteSupplierAddressesServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierAddressesRequest $request): JsonResponse
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
            'data' => SupplierAddressesResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierAddressesResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierAddressesResource($result->valueOrFail());
    }

    public function store(UpsertSupplierAddressesRequest $request): JsonResponse|SupplierAddressesResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierAddressesResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierAddressesRequest $request, int|string $id): JsonResponse|SupplierAddressesResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierAddressesResource($result->valueOrFail());
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