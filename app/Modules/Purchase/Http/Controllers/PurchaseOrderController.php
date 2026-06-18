<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\PurchaseProcurementBalanceService;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;

final class PurchaseOrderController
{
    use ScopesPurchaseRequests;

    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);
        $this->assertAllowedStatus($request, PurchaseOrderStatus::cases());

        $query = $this->scope(PurchaseOrder::query(), $request)->with([
            'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy',
            'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
        ])
            ->withSum('lines as received_quantity', 'received_quantity')
            ->withSum('lines as invoiced_quantity', 'invoiced_quantity')
            ->withSum('lines as returned_quantity', 'returned_quantity');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('purchase_order_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $supplier) use ($search): void {
                        $supplier->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('supplier_number', 'like', "%{$search}%");
                    });
            });
        }

        foreach (['status', 'supplier_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        $this->applyProgressFilters($query, $request);

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_order_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_order_date', '<=', $request->input('date_to'));
        }

        return PurchaseOrderResource::collection($query->latest('purchase_order_date')->paginate($request->perPage()));
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CREATE);

        return (new PurchaseOrderResource($service->create($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListPurchaseDocumentRequest $request, int $order): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        return new PurchaseOrderResource($this->scope(PurchaseOrder::query(), $request)
            ->with([
                'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy', 'closedBy',
                'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
            ])
            ->withSum('lines as received_quantity', 'received_quantity')
            ->withSum('lines as invoiced_quantity', 'invoiced_quantity')
            ->withSum('lines as returned_quantity', 'returned_quantity')
            ->findOrFail($order));
    }

    public function update(UpdatePurchaseOrderRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_UPDATE);

        return new PurchaseOrderResource($service->update($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->toData()));
    }

    public function destroy(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_DELETE);

        $service->delete($this->scope(PurchaseOrder::query(), $request)->findOrFail($order));

        return response()->json(status: 204);
    }

    public function approve(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_APPROVE);

        return new PurchaseOrderResource($service->approve($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function submit(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_SUBMIT);

        return new PurchaseOrderResource($service->submit($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function cancel(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CANCEL);

        return new PurchaseOrderResource($service->cancel($this->scope(PurchaseOrder::query(), $request)->findOrFail($order)));
    }

    public function close(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CLOSE);

        return new PurchaseOrderResource($service->close($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function supplierItemMappings(ListPurchaseDocumentRequest $request, int $supplier): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        $supplierModel = $this->scope(Supplier::query(), $request)->findOrFail($supplier);

        return SupplierItemMappingResource::collection(SupplierItemMapping::query()
            ->where('supplier_id', $supplierModel->getKey())
            ->with(['item', 'variant', 'defaultPurchaseUom'])
            ->where('is_active', true)
            ->paginate($request->perPage()));
    }

    private function applyProgressFilters(Builder $query, ListPurchaseDocumentRequest $request): void
    {
        $balances = app(PurchaseProcurementBalanceService::class);
        foreach (['receipt_status', 'invoice_status', 'return_status'] as $filter) {
            if ($request->filled($filter)) {
                $balances->applyPurchaseOrderProgressFilter($query, $filter, (string) $request->input($filter));
            }
        }
    }
}
