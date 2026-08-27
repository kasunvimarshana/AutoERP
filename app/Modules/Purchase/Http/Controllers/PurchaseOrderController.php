<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Purchase\Constants\PurchaseAuditEvent;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseAuditService;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseDocumentPresentationService;
use Modules\Purchase\Services\PurchaseOrderPrintService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\PurchaseProcurementBalanceService;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;

final class PurchaseOrderController
{
    use ScopesPurchaseRequests;

    public function __construct(
        private readonly PurchaseAuthorizationService $authorization,
        private readonly PurchaseDocumentPresentationService $presentation,
        private readonly PurchaseAuditService $audit,
    ) {}

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

        $orders = $query->latest('purchase_order_date')->paginate($request->perPage());
        $this->presentation->preparePurchaseOrders($orders->getCollection());

        return PurchaseOrderResource::collection($orders);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_CREATE);

        try {
            $order = $service->create($request->toData());
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_CREATED, 'purchase_order', $order);

            return (new PurchaseOrderResource($this->presentation->preparePurchaseOrder($order)))
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

        $order = $this->scope(PurchaseOrder::query(), $request)
            ->with([
                'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy', 'closedBy',
                'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
            ])
            ->withSum('lines as received_quantity', 'received_quantity')
            ->withSum('lines as invoiced_quantity', 'invoiced_quantity')
            ->withSum('lines as returned_quantity', 'returned_quantity')
            ->findOrFail($order);

        return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($order, true));
    }

    public function pdf(
        ListPurchaseDocumentRequest $request,
        int $order,
        PurchaseOrderPrintService $prints,
    ): Response {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_VIEW);

        $model = $prints->findScoped($order, $request->tenantId(), $request->organizationUnitId());
        abort_if($model === null, 404);

        $html = view('purchase.order-pdf', $prints->viewData($model))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper(PurchaseOrderPrintService::PDF_PAPER_SIZE, PurchaseOrderPrintService::PDF_ORIENTATION);
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$prints->filename($model).'"');
    }

    public function update(UpdatePurchaseOrderRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_UPDATE);

        try {
            $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
            $before = $model->attributesToArray();
            $updated = $service->update(
                $model,
                $request->toData(),
                $request->expectedVersion(),
            );
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_UPDATED, 'purchase_order', $updated, $before);

            return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($updated));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'purchase_order_number' => [$exception->getMessage()],
            ]);
        }
    }

    public function destroy(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_DELETE);

        $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
        $before = $model->attributesToArray();
        $service->delete($model, $request->expectedVersion());
        $this->audit->recordDeletedDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_DELETED, 'purchase_order', $model, $before);

        return response()->json(status: 204);
    }

    public function approve(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::ORDERS_APPROVE);

        try {
            $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
            $before = $model->attributesToArray();
            $updated = $service->approve(
                $model,
                $request->currentUserId(),
                $request->expectedVersion(),
            );
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_APPROVED, 'purchase_order', $updated, $before);

            return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($updated));
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
            $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
            $before = $model->attributesToArray();
            $updated = $service->submit(
                $model,
                $request->currentUserId(),
                $request->expectedVersion(),
            );
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_SUBMITTED, 'purchase_order', $updated, $before);

            return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($updated));
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
            $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
            $before = $model->attributesToArray();
            $updated = $service->cancel(
                $model,
                $request->expectedVersion(),
            );
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_CANCELLED, 'purchase_order', $updated, $before);

            return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($updated));
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
            $model = $this->scope(PurchaseOrder::query(), $request)->findOrFail($order);
            $before = $model->attributesToArray();
            $updated = $service->close(
                $model,
                $request->currentUserId(),
                $request->expectedVersion(),
            );
            $this->audit->recordDocumentEvent(PurchaseAuditEvent::PURCHASE_ORDER_CLOSED, 'purchase_order', $updated, $before);

            return new PurchaseOrderResource($this->presentation->preparePurchaseOrder($updated));
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
