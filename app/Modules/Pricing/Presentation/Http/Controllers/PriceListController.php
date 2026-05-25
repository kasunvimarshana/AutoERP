<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\GetPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\ListPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\UpdatePriceListServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\ListPriceListRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertPriceListRequest;
use Modules\Pricing\Presentation\Http\Resources\PriceListResource;

final class PriceListController extends Controller
{
    public function __construct(
        private readonly ListPriceListsServiceInterface $listService,
        private readonly GetPriceListServiceInterface $getService,
        private readonly CreatePriceListServiceInterface $createService,
        private readonly UpdatePriceListServiceInterface $updateService,
        private readonly DeletePriceListServiceInterface $deleteService,
    ) {
    }

    public function index(ListPriceListRequest $request): JsonResponse
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
            'data' => PriceListResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PriceListResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PriceListResource($result->valueOrFail());
    }

    public function store(UpsertPriceListRequest $request): JsonResponse|PriceListResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PriceListResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPriceListRequest $request, int|string $id): JsonResponse|PriceListResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PriceListResource($result->valueOrFail());
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