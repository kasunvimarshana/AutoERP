<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Sales\Application\Contracts\CreateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesPaymentServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesRefundServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesInvoiceServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Infrastructure\Http\Requests\ListSalesInvoiceRequest;
use Modules\Sales\Infrastructure\Http\Requests\PostSalesInvoiceRequest;
use Modules\Sales\Infrastructure\Http\Requests\RecordSalesPaymentRequest;
use Modules\Sales\Infrastructure\Http\Requests\RecordSalesRefundRequest;
use Modules\Sales\Infrastructure\Http\Requests\StoreSalesInvoiceRequest;
use Modules\Sales\Infrastructure\Http\Requests\UpdateSalesInvoiceRequest;
use Modules\Sales\Infrastructure\Http\Resources\SalesInvoiceCollection;
use Modules\Sales\Infrastructure\Http\Resources\SalesInvoiceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SalesInvoiceController extends AuthorizedController
{
    public function __construct(
        protected CreateSalesInvoiceServiceInterface $createService,
        protected UpdateSalesInvoiceServiceInterface $updateService,
        protected DeleteSalesInvoiceServiceInterface $deleteService,
        protected FindSalesInvoiceServiceInterface $findService,
        protected PostSalesInvoiceServiceInterface $postService,
        protected RecordSalesPaymentServiceInterface $recordPaymentService,
        protected RecordSalesRefundServiceInterface $recordRefundService,
    ) {}

    public function index(ListSalesInvoiceRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SalesInvoice::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'sales_order_id' => $validated['sales_order_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new SalesInvoiceCollection($result))->response();
    }

    public function store(StoreSalesInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', SalesInvoice::class);
        $entity = $this->createService->execute($request->validated());

        return (new SalesInvoiceResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $salesInvoice): SalesInvoiceResource
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('view', $entity);

        return new SalesInvoiceResource($entity);
    }

    public function update(UpdateSalesInvoiceRequest $request, int $salesInvoice): SalesInvoiceResource
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $salesInvoice;
        $updated = $this->updateService->execute($payload);

        return new SalesInvoiceResource($updated);
    }

    public function destroy(int $salesInvoice): JsonResponse
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $salesInvoice]);

        return Response::json(['message' => 'Sales invoice deleted successfully']);
    }

    public function post(PostSalesInvoiceRequest $request, int $salesInvoice): SalesInvoiceResource
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('update', $entity);
        $posted = $this->postService->execute(['id' => $salesInvoice]);

        return new SalesInvoiceResource($posted);
    }

    public function recordPayment(RecordSalesPaymentRequest $request, int $salesInvoice): SalesInvoiceResource
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['invoice_id'] = $salesInvoice;
        $payload['tenant_id'] = $entity->getTenantId();
        $updated = $this->recordPaymentService->execute($payload);

        return new SalesInvoiceResource($updated);
    }

    public function recordRefund(RecordSalesRefundRequest $request, int $salesInvoice): SalesInvoiceResource
    {
        $entity = $this->findOrFail($salesInvoice);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['invoice_id'] = $salesInvoice;
        $payload['tenant_id'] = $entity->getTenantId();
        $updated = $this->recordRefundService->execute($payload);

        return new SalesInvoiceResource($updated);
    }

    private function findOrFail(int $id): SalesInvoice
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Sales invoice not found.');
        }

        return $entity;
    }
}
