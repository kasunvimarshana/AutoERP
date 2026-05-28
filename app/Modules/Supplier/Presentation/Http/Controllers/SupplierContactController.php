<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\GetSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\ListSupplierContactsServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierContactRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierContactRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierContactResource;

final class SupplierContactController extends Controller
{
    public function __construct(
        private readonly ListSupplierContactsServiceInterface $listService,
        private readonly GetSupplierContactServiceInterface $getService,
        private readonly CreateSupplierContactServiceInterface $createService,
        private readonly UpdateSupplierContactServiceInterface $updateService,
        private readonly DeleteSupplierContactServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierContactRequest $request): JsonResponse
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
            'data' => SupplierContactResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierContactResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierContactResource($result->valueOrFail());
    }

    public function store(UpsertSupplierContactRequest $request): JsonResponse|SupplierContactResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierContactResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierContactRequest $request, int|string $id): JsonResponse|SupplierContactResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierContactResource($result->valueOrFail());
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
