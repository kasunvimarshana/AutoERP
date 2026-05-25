<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\CreatePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\DeletePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\GetPurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\ListPurchaseReturnLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\UpdatePurchaseReturnLineServiceInterface;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseReturnLineRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseReturnLineRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseReturnLineResource;

final class PurchaseReturnLineController extends Controller
{
    public function __construct(
        private readonly ListPurchaseReturnLinesServiceInterface $listService,
        private readonly GetPurchaseReturnLineServiceInterface $getService,
        private readonly CreatePurchaseReturnLineServiceInterface $createService,
        private readonly UpdatePurchaseReturnLineServiceInterface $updateService,
        private readonly DeletePurchaseReturnLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListPurchaseReturnLineRequest $request): JsonResponse
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
            'data' => PurchaseReturnLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PurchaseReturnLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PurchaseReturnLineResource($result->valueOrFail());
    }

    public function store(UpsertPurchaseReturnLineRequest $request): JsonResponse|PurchaseReturnLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PurchaseReturnLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPurchaseReturnLineRequest $request, int|string $id): JsonResponse|PurchaseReturnLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PurchaseReturnLineResource($result->valueOrFail());
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