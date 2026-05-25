<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\GetSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\ListSupplierPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\UpdateSupplierPriceListServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\ListSupplierPriceListRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertSupplierPriceListRequest;
use Modules\Pricing\Presentation\Http\Resources\SupplierPriceListResource;

final class SupplierPriceListController extends Controller
{
    public function __construct(
        private readonly ListSupplierPriceListsServiceInterface $listService,
        private readonly GetSupplierPriceListServiceInterface $getService,
        private readonly CreateSupplierPriceListServiceInterface $createService,
        private readonly UpdateSupplierPriceListServiceInterface $updateService,
        private readonly DeleteSupplierPriceListServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierPriceListRequest $request): JsonResponse
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
            'data' => SupplierPriceListResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierPriceListResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierPriceListResource($result->valueOrFail());
    }

    public function store(UpsertSupplierPriceListRequest $request): JsonResponse|SupplierPriceListResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierPriceListResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierPriceListRequest $request, int|string $id): JsonResponse|SupplierPriceListResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierPriceListResource($result->valueOrFail());
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