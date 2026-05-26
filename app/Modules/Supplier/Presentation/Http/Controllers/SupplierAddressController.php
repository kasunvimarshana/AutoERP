<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\GetSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\ListSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierAddressRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierAddressRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierAddressResource;

final class SupplierAddressController extends Controller
{
    public function __construct(
        private readonly ListSupplierAddressesServiceInterface $listService,
        private readonly GetSupplierAddressServiceInterface $getService,
        private readonly CreateSupplierAddressServiceInterface $createService,
        private readonly UpdateSupplierAddressServiceInterface $updateService,
        private readonly DeleteSupplierAddressServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierAddressRequest $request): JsonResponse
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
            'data' => SupplierAddressResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierAddressResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierAddressResource($result->valueOrFail());
    }

    public function store(UpsertSupplierAddressRequest $request): JsonResponse|SupplierAddressResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierAddressResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierAddressRequest $request, int|string $id): JsonResponse|SupplierAddressResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierAddressResource($result->valueOrFail());
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