<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Sales\Application\Contracts\ApproveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesReturnServiceInterface;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Infrastructure\Http\Requests\ApproveSalesReturnRequest;
use Modules\Sales\Infrastructure\Http\Requests\ListSalesReturnRequest;
use Modules\Sales\Infrastructure\Http\Requests\ReceiveSalesReturnRequest;
use Modules\Sales\Infrastructure\Http\Requests\StoreSalesReturnRequest;
use Modules\Sales\Infrastructure\Http\Requests\UpdateSalesReturnRequest;
use Modules\Sales\Infrastructure\Http\Resources\SalesReturnCollection;
use Modules\Sales\Infrastructure\Http\Resources\SalesReturnResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SalesReturnController extends AuthorizedController
{
    public function __construct(
        protected CreateSalesReturnServiceInterface $createService,
        protected UpdateSalesReturnServiceInterface $updateService,
        protected DeleteSalesReturnServiceInterface $deleteService,
        protected FindSalesReturnServiceInterface $findService,
        protected ApproveSalesReturnServiceInterface $approveService,
        protected ReceiveSalesReturnServiceInterface $receiveService,
    ) {}

    public function index(ListSalesReturnRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SalesReturn::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'original_sales_order_id' => $validated['original_sales_order_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new SalesReturnCollection($result))->response();
    }

    public function store(StoreSalesReturnRequest $request): JsonResponse
    {
        $this->authorize('create', SalesReturn::class);
        $entity = $this->createService->execute($request->validated());

        return (new SalesReturnResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $salesReturn): SalesReturnResource
    {
        $entity = $this->findOrFail($salesReturn);
        $this->authorize('view', $entity);

        return new SalesReturnResource($entity);
    }

    public function update(UpdateSalesReturnRequest $request, int $salesReturn): SalesReturnResource
    {
        $entity = $this->findOrFail($salesReturn);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $salesReturn;
        $updated = $this->updateService->execute($payload);

        return new SalesReturnResource($updated);
    }

    public function destroy(int $salesReturn): JsonResponse
    {
        $entity = $this->findOrFail($salesReturn);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $salesReturn]);

        return Response::json(['message' => 'Sales return deleted successfully']);
    }

    public function approve(ApproveSalesReturnRequest $request, int $salesReturn): SalesReturnResource
    {
        $entity = $this->findOrFail($salesReturn);
        $this->authorize('update', $entity);
        $approved = $this->approveService->execute(['id' => $salesReturn]);

        return new SalesReturnResource($approved);
    }

    public function receive(ReceiveSalesReturnRequest $request, int $salesReturn): SalesReturnResource
    {
        $entity = $this->findOrFail($salesReturn);
        $this->authorize('update', $entity);
        $received = $this->receiveService->execute(['id' => $salesReturn]);

        return new SalesReturnResource($received);
    }

    private function findOrFail(int $id): SalesReturn
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Sales return not found.');
        }

        return $entity;
    }
}
