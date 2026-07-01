<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
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

        $query = $this->scope(PurchaseOrder::query(), $request)
            ->select('purchase_orders.*')
            ->with([
            'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy',
            'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
        ])
            ->withSum('lines as received_quantity', 'received_quantity')
            ->withSum('lines as invoiced_quantity', 'invoiced_quantity')
            ->withSum('lines as returned_quantity', 'returned_quantity');
        $this->addCapabilityProjection($query);

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

        try {
            return (new PurchaseOrderResource($service->create($request->toData())))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order_number' => [$exception->getMessage()],
            ]);
        }
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

        try {
            return new PurchaseOrderResource($service->update($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->toData()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order_number' => [$exception->getMessage()],
            ]);
        }
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

        try {
            return new PurchaseOrderResource($service->approve($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order' => [$exception->getMessage()],
            ]);
        }
    }

    public function submit(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_SUBMIT);

        try {
            return new PurchaseOrderResource($service->submit($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order' => [$exception->getMessage()],
            ]);
        }
    }

    public function cancel(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CANCEL);

        try {
            return new PurchaseOrderResource($service->cancel($this->scope(PurchaseOrder::query(), $request)->findOrFail($order)));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order' => [$exception->getMessage()],
            ]);
        }
    }

    public function close(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CLOSE);

        try {
            return new PurchaseOrderResource($service->close($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order' => [$exception->getMessage()],
            ]);
        }
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

    private function addCapabilityProjection(Builder $query): void
    {
        $query
            ->selectSub(function ($sub): void {
                $sub->from('goods_receipt_notes')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('goods_receipt_notes.purchase_order_id', 'purchase_orders.id')
                    ->where('goods_receipt_notes.status', 'draft');
            }, 'draft_goods_receipts_count')
            ->selectSub(function ($sub): void {
                $sub->from('purchase_invoice_links')
                    ->join('invoices', 'invoices.id', '=', 'purchase_invoice_links.invoice_id')
                    ->selectRaw('COUNT(DISTINCT invoices.id)')
                    ->whereIn('invoices.status', ['draft', 'approved'])
                    ->where(function ($scope): void {
                        $scope->where(function ($direct): void {
                            $direct->where('purchase_invoice_links.source_type', 'purchase_order')
                                ->whereColumn('purchase_invoice_links.source_id', 'purchase_orders.id');
                        })->orWhere(function ($viaReceipt): void {
                            $viaReceipt->where('purchase_invoice_links.source_type', 'goods_receipt_note')
                                ->whereIn('purchase_invoice_links.source_id', function ($receipts): void {
                                    $receipts->select('id')
                                        ->from('goods_receipt_notes')
                                        ->whereColumn('goods_receipt_notes.purchase_order_id', 'purchase_orders.id');
                                });
                        });
                    });
            }, 'unresolved_purchase_invoices_count')
            ->selectSub(function ($sub): void {
                $sub->from('purchase_returns')
                    ->selectRaw('COUNT(*)')
                    ->where('purchase_returns.source_type', 'goods_receipt_note')
                    ->whereIn('purchase_returns.status', ['draft', 'approved'])
                    ->whereIn('purchase_returns.source_id', function ($receipts): void {
                        $receipts->select('id')
                            ->from('goods_receipt_notes')
                            ->whereColumn('goods_receipt_notes.purchase_order_id', 'purchase_orders.id');
                    });
            }, 'unresolved_purchase_returns_count')
            ->selectSub(function ($sub): void {
                $sub->from('purchase_debit_notes')
                    ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_debit_notes.purchase_return_id')
                    ->selectRaw('COUNT(*)')
                    ->where('purchase_returns.source_type', 'goods_receipt_note')
                    ->whereIn('purchase_returns.source_id', function ($receipts): void {
                        $receipts->select('id')
                            ->from('goods_receipt_notes')
                            ->whereColumn('goods_receipt_notes.purchase_order_id', 'purchase_orders.id');
                    })
                    ->where(function ($notes): void {
                        $notes->whereIn('purchase_debit_notes.status', ['draft', 'approved'])
                            ->orWhere(function ($posted): void {
                                $posted->where('purchase_debit_notes.status', 'posted')
                                    ->whereRaw('purchase_debit_notes.remaining_amount > 0');
                            });
                    });
            }, 'unresolved_purchase_debit_notes_count');
    }
}
