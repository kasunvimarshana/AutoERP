<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\GetPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\ListPriceListItemsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Presentation\Http\Requests\ListPriceListItemRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertPriceListItemRequest;
use Modules\Pricing\Presentation\Http\Resources\PriceListItemResource;

final class PriceListItemController extends Controller
{
    public function __construct(
        private readonly ListPriceListItemsServiceInterface $listService,
        private readonly GetPriceListItemServiceInterface $getService,
        private readonly CreatePriceListItemServiceInterface $createService,
        private readonly UpdatePriceListItemServiceInterface $updateService,
        private readonly DeletePriceListItemServiceInterface $deleteService,
    ) {}

    public function index(ListPriceListItemRequest $request): JsonResponse
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
            'data' => PriceListItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PriceListItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PriceListItemResource($result->valueOrFail());
    }

    public function store(UpsertPriceListItemRequest $request): JsonResponse|PriceListItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PriceListItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPriceListItemRequest $request, int|string $id): JsonResponse|PriceListItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PriceListItemResource($result->valueOrFail());
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
