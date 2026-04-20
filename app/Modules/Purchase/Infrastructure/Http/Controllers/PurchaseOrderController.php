<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Infrastructure\Http\Requests\ConfirmPurchaseOrderRequest;
use Modules\Purchase\Infrastructure\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Infrastructure\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Infrastructure\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseOrderCollection;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseOrderResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PurchaseOrderController extends AuthorizedController
{
    public function __construct(
        protected CreatePurchaseOrderServiceInterface $createService,
        protected UpdatePurchaseOrderServiceInterface $updateService,
        protected DeletePurchaseOrderServiceInterface $deleteService,
        protected FindPurchaseOrderServiceInterface $findService,
        protected ConfirmPurchaseOrderServiceInterface $confirmService,
    ) {}

    public function index(ListPurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PurchaseOrderCollection($result))->response();
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);
        $entity = $this->createService->execute($request->validated());

        return (new PurchaseOrderResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $purchaseOrder): PurchaseOrderResource
    {
        $entity = $this->findOrFail($purchaseOrder);
        $this->authorize('view', $entity);

        return new PurchaseOrderResource($entity);
    }

    public function update(UpdatePurchaseOrderRequest $request, int $purchaseOrder): PurchaseOrderResource
    {
        $entity = $this->findOrFail($purchaseOrder);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $purchaseOrder;
        $updated = $this->updateService->execute($payload);

        return new PurchaseOrderResource($updated);
    }

    public function destroy(int $purchaseOrder): JsonResponse
    {
        $entity = $this->findOrFail($purchaseOrder);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $purchaseOrder]);

        return Response::json(['message' => 'Purchase order deleted successfully']);
    }

    public function confirm(ConfirmPurchaseOrderRequest $request, int $purchaseOrder): PurchaseOrderResource
    {
        $entity = $this->findOrFail($purchaseOrder);
        $this->authorize('update', $entity);
        $confirmed = $this->confirmService->execute(['id' => $purchaseOrder]);

        return new PurchaseOrderResource($confirmed);
    }

    private function findOrFail(int $id): PurchaseOrder
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Purchase order not found.');
        }

        return $entity;
    }
}
