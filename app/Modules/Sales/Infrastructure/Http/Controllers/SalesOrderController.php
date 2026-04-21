<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Infrastructure\Http\Requests\CancelSalesOrderRequest;
use Modules\Sales\Infrastructure\Http\Requests\ConfirmSalesOrderRequest;
use Modules\Sales\Infrastructure\Http\Requests\ListSalesOrderRequest;
use Modules\Sales\Infrastructure\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Infrastructure\Http\Requests\UpdateSalesOrderRequest;
use Modules\Sales\Infrastructure\Http\Resources\SalesOrderCollection;
use Modules\Sales\Infrastructure\Http\Resources\SalesOrderResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SalesOrderController extends AuthorizedController
{
    public function __construct(
        protected CreateSalesOrderServiceInterface $createService,
        protected UpdateSalesOrderServiceInterface $updateService,
        protected DeleteSalesOrderServiceInterface $deleteService,
        protected FindSalesOrderServiceInterface $findService,
        protected ConfirmSalesOrderServiceInterface $confirmService,
        protected CancelSalesOrderServiceInterface $cancelService,
    ) {}

    public function index(ListSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new SalesOrderCollection($result))->response();
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);
        $entity = $this->createService->execute($request->validated());

        return (new SalesOrderResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $salesOrder): SalesOrderResource
    {
        $entity = $this->findOrFail($salesOrder);
        $this->authorize('view', $entity);

        return new SalesOrderResource($entity);
    }

    public function update(UpdateSalesOrderRequest $request, int $salesOrder): SalesOrderResource
    {
        $entity = $this->findOrFail($salesOrder);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $salesOrder;
        $updated = $this->updateService->execute($payload);

        return new SalesOrderResource($updated);
    }

    public function destroy(int $salesOrder): JsonResponse
    {
        $entity = $this->findOrFail($salesOrder);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $salesOrder]);

        return Response::json(['message' => 'Sales order deleted successfully']);
    }

    public function confirm(ConfirmSalesOrderRequest $request, int $salesOrder): SalesOrderResource
    {
        $entity = $this->findOrFail($salesOrder);
        $this->authorize('update', $entity);
        $confirmed = $this->confirmService->execute(['id' => $salesOrder]);

        return new SalesOrderResource($confirmed);
    }

    public function cancel(CancelSalesOrderRequest $request, int $salesOrder): SalesOrderResource
    {
        $entity = $this->findOrFail($salesOrder);
        $this->authorize('update', $entity);
        $cancelled = $this->cancelService->execute(['id' => $salesOrder]);

        return new SalesOrderResource($cancelled);
    }

    private function findOrFail(int $id): SalesOrder
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Sales order not found.');
        }

        return $entity;
    }
}
