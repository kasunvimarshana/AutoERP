<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\GetPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\ListPurchaseOrdersServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Purchase\Application\UseCases\ConfirmPurchaseOrderAction;
use Modules\Purchase\Application\UseCases\CreatePurchaseOrderAction;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseOrderRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Domain\Services\PurchaseOrderService;
use Throwable;

final class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly ListPurchaseOrdersServiceInterface $listService,
        private readonly GetPurchaseOrderServiceInterface $getService,
        private readonly CreatePurchaseOrderServiceInterface $createService,
        private readonly UpdatePurchaseOrderServiceInterface $updateService,
        private readonly DeletePurchaseOrderServiceInterface $deleteService,
        private readonly CreatePurchaseOrderAction $createPurchaseOrderAction,
        private readonly ConfirmPurchaseOrderAction $confirmPurchaseOrderAction,
        private readonly PurchaseOrderService $purchaseOrderService,
    ) {
    }

    public function index(ListPurchaseOrderRequest $request): JsonResponse
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
            'data' => PurchaseOrderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PurchaseOrderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PurchaseOrderResource($result->valueOrFail());
    }

    public function store(UpsertPurchaseOrderRequest $request): JsonResponse|PurchaseOrderResource
    {
        try {
            $record = $this->createPurchaseOrderAction->execute(new CreatePurchaseOrderDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PurchaseOrderResource($record))->response()->setStatusCode(201);
    }

    public function update(UpsertPurchaseOrderRequest $request, int|string $id): JsonResponse|PurchaseOrderResource
    {
        try {
            $record = $this->purchaseOrderService->update((int) $id, $request->validated());
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new PurchaseOrderResource($record);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function confirm(int|string $id): JsonResponse|PurchaseOrderResource
    {
        try {
            return new PurchaseOrderResource($this->confirmPurchaseOrderAction->execute((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function cancel(int|string $id): JsonResponse|PurchaseOrderResource
    {
        try {
            return new PurchaseOrderResource($this->purchaseOrderService->cancel((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
