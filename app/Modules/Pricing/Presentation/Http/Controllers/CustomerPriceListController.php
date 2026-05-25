<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\GetCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\ListCustomerPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\UpdateCustomerPriceListServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\ListCustomerPriceListRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertCustomerPriceListRequest;
use Modules\Pricing\Presentation\Http\Resources\CustomerPriceListResource;

final class CustomerPriceListController extends Controller
{
    public function __construct(
        private readonly ListCustomerPriceListsServiceInterface $listService,
        private readonly GetCustomerPriceListServiceInterface $getService,
        private readonly CreateCustomerPriceListServiceInterface $createService,
        private readonly UpdateCustomerPriceListServiceInterface $updateService,
        private readonly DeleteCustomerPriceListServiceInterface $deleteService,
    ) {
    }

    public function index(ListCustomerPriceListRequest $request): JsonResponse
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
            'data' => CustomerPriceListResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CustomerPriceListResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CustomerPriceListResource($result->valueOrFail());
    }

    public function store(UpsertCustomerPriceListRequest $request): JsonResponse|CustomerPriceListResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CustomerPriceListResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCustomerPriceListRequest $request, int|string $id): JsonResponse|CustomerPriceListResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerPriceListResource($result->valueOrFail());
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