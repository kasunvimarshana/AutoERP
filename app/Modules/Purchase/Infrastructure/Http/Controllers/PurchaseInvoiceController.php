<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Infrastructure\Http\Requests\ApprovePurchaseInvoiceRequest;
use Modules\Purchase\Infrastructure\Http\Requests\ListPurchaseInvoiceRequest;
use Modules\Purchase\Infrastructure\Http\Requests\StorePurchaseInvoiceRequest;
use Modules\Purchase\Infrastructure\Http\Requests\UpdatePurchaseInvoiceRequest;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseInvoiceCollection;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseInvoiceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PurchaseInvoiceController extends AuthorizedController
{
    public function __construct(
        protected CreatePurchaseInvoiceServiceInterface $createService,
        protected UpdatePurchaseInvoiceServiceInterface $updateService,
        protected DeletePurchaseInvoiceServiceInterface $deleteService,
        protected FindPurchaseInvoiceServiceInterface $findService,
        protected ApprovePurchaseInvoiceServiceInterface $approveService,
    ) {}

    public function index(ListPurchaseInvoiceRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseInvoice::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'invoice_number' => $validated['invoice_number'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PurchaseInvoiceCollection($result))->response();
    }

    public function store(StorePurchaseInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseInvoice::class);
        $entity = $this->createService->execute($request->validated());

        return (new PurchaseInvoiceResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $invoice): PurchaseInvoiceResource
    {
        $entity = $this->findOrFail($invoice);
        $this->authorize('view', $entity);

        return new PurchaseInvoiceResource($entity);
    }

    public function update(UpdatePurchaseInvoiceRequest $request, int $invoice): PurchaseInvoiceResource
    {
        $entity = $this->findOrFail($invoice);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $invoice;
        $updated = $this->updateService->execute($payload);

        return new PurchaseInvoiceResource($updated);
    }

    public function destroy(int $invoice): JsonResponse
    {
        $entity = $this->findOrFail($invoice);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $invoice]);

        return Response::json(['message' => 'Purchase invoice deleted successfully']);
    }

    public function approve(ApprovePurchaseInvoiceRequest $request, int $invoice): PurchaseInvoiceResource
    {
        $entity = $this->findOrFail($invoice);
        $this->authorize('update', $entity);
        $approved = $this->approveService->execute(['id' => $invoice]);

        return new PurchaseInvoiceResource($approved);
    }

    private function findOrFail(int $id): PurchaseInvoice
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Purchase invoice not found.');
        }

        return $entity;
    }
}
