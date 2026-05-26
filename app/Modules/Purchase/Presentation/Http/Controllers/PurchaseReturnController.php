<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\GetPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\ListPurchaseReturnsServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\DTOs\CreatePurchaseReturnDTO;
use Modules\Purchase\Application\UseCases\CreatePurchaseReturnAction;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseReturnRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseReturnRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseReturnResource;
use Throwable;

final class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly ListPurchaseReturnsServiceInterface $listService,
        private readonly GetPurchaseReturnServiceInterface $getService,
        private readonly CreatePurchaseReturnServiceInterface $createService,
        private readonly UpdatePurchaseReturnServiceInterface $updateService,
        private readonly DeletePurchaseReturnServiceInterface $deleteService,
        private readonly CreatePurchaseReturnAction $createPurchaseReturnAction,
    ) {
    }

    public function index(ListPurchaseReturnRequest $request): JsonResponse
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
            'data' => PurchaseReturnResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PurchaseReturnResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PurchaseReturnResource($result->valueOrFail());
    }

    public function store(UpsertPurchaseReturnRequest $request): JsonResponse|PurchaseReturnResource
    {
        try {
            $record = $this->createPurchaseReturnAction->execute(new CreatePurchaseReturnDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PurchaseReturnResource($record))->response()->setStatusCode(201);
    }

    public function update(UpsertPurchaseReturnRequest $request, int|string $id): JsonResponse|PurchaseReturnResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PurchaseReturnResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function approve(int|string $id): JsonResponse|PurchaseReturnResource
    {
        try {
            return new PurchaseReturnResource($this->createPurchaseReturnAction->approve((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
