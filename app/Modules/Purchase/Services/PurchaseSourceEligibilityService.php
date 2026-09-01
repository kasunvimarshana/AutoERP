<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseSourceEligibilityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseProcurementBalanceService $balances,
    ) {}

    public function receivablePurchaseOrders(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $supplierId,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->orderQuery($tenantId, $organizationUnitId, $supplierId, $search)
            ->where('status', PurchaseOrderStatus::Approved->value)
            ->whereHas('lines', fn (Builder $query) => $this->balances->wherePurchaseOrderLineReceivable($query))
            ->latest('purchase_order_date')
            ->paginate($perPage);
    }

    public function invoiceablePurchaseOrders(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $supplierId,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->orderQuery($tenantId, $organizationUnitId, $supplierId, $search)
            ->where('status', PurchaseOrderStatus::Approved->value)
            ->whereHas('lines', fn (Builder $query) => $this->balances->wherePurchaseOrderLineInvoiceable($query))
            ->latest('purchase_order_date')
            ->paginate($perPage);
    }

    public function invoiceableGoodsReceipts(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $supplierId,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->goodsReceiptQuery($tenantId, $organizationUnitId, $supplierId, $search)
            ->where('status', GoodsReceiptNoteStatus::Posted->value)
            ->whereHas('lines', function (Builder $query): void {
                $this->balances->whereGoodsReceiptLineInvoiceable($query)
                    ->where(function (Builder $scope): void {
                        $scope->whereNull('purchase_order_line_id')
                            ->orWhereHas('purchaseOrderLine', fn (Builder $poLine): Builder => $poLine
                                ->whereRaw($this->balances->purchaseOrderInvoiceableRemainingSql().' > 0')
                                ->whereDoesntHave('order', fn (Builder $order): Builder => $order
                                    ->whereIn('status', [PurchaseOrderStatus::Closed->value, PurchaseOrderStatus::Cancelled->value])));
                    });
            })
            ->latest('received_date')
            ->paginate($perPage);
    }

    public function returnableGoodsReceipts(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $supplierId,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->goodsReceiptQuery($tenantId, $organizationUnitId, $supplierId, $search)
            ->where('status', GoodsReceiptNoteStatus::Posted->value)
            ->whereHas('lines', function (Builder $query): void {
                $this->balances->whereGoodsReceiptLineReturnable($query)
                    ->where(function (Builder $scope): void {
                        $scope->whereNull('purchase_order_line_id')
                            ->orWhereHas('purchaseOrderLine', fn (Builder $poLine): Builder => $poLine
                                ->whereDoesntHave('order', fn (Builder $order): Builder => $order
                                    ->whereIn('status', [PurchaseOrderStatus::Closed->value, PurchaseOrderStatus::Cancelled->value])));
                    });
            })
            ->latest('received_date')
            ->paginate($perPage);
    }

    public function outstandingSupplierInvoices(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $supplierId,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return Invoice::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'), fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('invoice_type', InvoiceType::Purchase->value)
            ->where('direction', InvoiceDirection::Inbound->value)
            ->whereIn('status', [InvoiceStatus::Posted->value, InvoiceStatus::PartiallyPaid->value])
            ->whereRaw('balance_due > 0')
            ->when($supplierId !== null, fn (Builder $query) => $query->where('party_id', $supplierId))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('invoice_number', 'like', '%'.$search.'%');
            }))
            ->latest('invoice_date')
            ->paginate($perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function receivableLines(PurchaseOrder $order): array
    {
        if ($this->statusValue($order->status) !== PurchaseOrderStatus::Approved->value) {
            return [];
        }

        return $order->lines
            ->filter(fn (PurchaseOrderLine $line): bool => $this->math->compare($this->balances->remainingReceivableForPurchaseOrderLine($line), '0.000000') > 0)
            ->values()
            ->map(fn (PurchaseOrderLine $line): array => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'item_id' => $line->item_id,
                'item' => $this->summary($line->item, ['code', 'name', 'sku', 'tracking_type']),
                'item_variant_id' => $line->item_variant_id,
                'item_variant' => $this->summary($line->variant, ['code', 'name', 'sku']),
                'uom_id' => $line->uom_id,
                'uom' => $this->summary($line->uom, ['code', 'name', 'symbol']),
                'ordered_quantity' => (string) $line->ordered_quantity,
                'received_quantity' => (string) $line->received_quantity,
                'remaining_quantity' => $this->balances->remainingReceivableForPurchaseOrderLine($line),
                'unit_price' => (string) $line->unit_price,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function invoiceableOrderLines(PurchaseOrder $order): array
    {
        if ($this->statusValue($order->status) !== PurchaseOrderStatus::Approved->value) {
            return [];
        }

        return $order->lines
            ->map(function (PurchaseOrderLine $line): array {
                $remaining = $this->balances->remainingInvoiceableForPurchaseOrderLine($line);

                return [
                    'id' => (int) $line->getKey(),
                    'line_number' => (int) $line->line_number,
                    'item_id' => $line->item_id,
                    'item' => $this->summary($line->item, ['code', 'name', 'sku']),
                    'item_variant_id' => $line->item_variant_id,
                    'item_variant' => $this->summary($line->variant, ['code', 'name', 'sku']),
                    'uom_id' => $line->uom_id,
                    'uom' => $this->summary($line->uom, ['code', 'name', 'symbol']),
                    'ordered_quantity' => (string) $line->ordered_quantity,
                    'invoiced_quantity' => (string) $line->invoiced_quantity,
                    'remaining_invoiceable_quantity' => $remaining,
                    'can_invoice' => $this->math->compare($remaining, '0.000000') > 0,
                    'block_reason' => $this->math->compare($remaining, '0.000000') > 0 ? null : 'Fully invoiced.',
                    'unit_price' => (string) $line->unit_price,
                ];
            })
            ->filter(fn (array $line): bool => $line['can_invoice'])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function invoiceableGoodsReceiptLines(GoodsReceiptNote $grn): array
    {
        if ($this->statusValue($grn->status) !== GoodsReceiptNoteStatus::Posted->value) {
            return [];
        }

        return $grn->lines
            ->map(function (GoodsReceiptNoteLine $line): array {
                $remaining = $this->balances->remainingInvoiceableForGoodsReceiptLine($line);
                $blockedByOrder = $this->linkedPurchaseOrderClosed($line);
                $canInvoice = ! $blockedByOrder && $this->math->compare($remaining, '0.000000') > 0;

                return [
                    'id' => (int) $line->getKey(),
                    'purchase_order_line_id' => $line->purchase_order_line_id,
                    'item_id' => $line->item_id,
                    'item' => $this->summary($line->item, ['code', 'name', 'sku']),
                    'item_variant_id' => $line->item_variant_id,
                    'item_variant' => $this->summary($line->variant, ['code', 'name', 'sku']),
                    'uom_id' => $line->uom_id,
                    'uom' => $this->summary($line->uom, ['code', 'name', 'symbol']),
                    'accepted_quantity' => (string) $line->accepted_quantity,
                    'invoiced_quantity' => (string) $line->invoiced_quantity,
                    'remaining_invoiceable_quantity' => $remaining,
                    'can_invoice' => $canInvoice,
                    'block_reason' => $canInvoice
                        ? null
                        : ($blockedByOrder ? 'Linked purchase order is closed or cancelled.' : 'Fully invoiced through linked procurement quantity.'),
                    'unit_price' => (string) $line->unit_price,
                ];
            })
            ->filter(fn (array $line): bool => $line['can_invoice'])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function returnableGoodsReceiptLines(GoodsReceiptNote $grn): array
    {
        if ($this->statusValue($grn->status) !== GoodsReceiptNoteStatus::Posted->value) {
            return [];
        }

        return $grn->lines
            ->map(function (GoodsReceiptNoteLine $line): array {
                $remaining = $this->balances->remainingReturnableForGoodsReceiptLine($line);
                $blockedByOrder = $this->linkedPurchaseOrderClosed($line);
                $canReturn = ! $blockedByOrder && $this->math->compare($remaining, '0.000000') > 0;

                return [
                    'id' => (int) $line->getKey(),
                    'purchase_order_line_id' => $line->purchase_order_line_id,
                    'item_id' => $line->item_id,
                    'item' => $this->summary($line->item, ['code', 'name', 'sku']),
                    'item_variant_id' => $line->item_variant_id,
                    'item_variant' => $this->summary($line->variant, ['code', 'name', 'sku']),
                    'uom_id' => $line->uom_id,
                    'uom' => $this->summary($line->uom, ['code', 'name', 'symbol']),
                    'accepted_quantity' => (string) $line->accepted_quantity,
                    'returned_quantity' => (string) $line->returned_quantity,
                    'remaining_returnable_quantity' => $remaining,
                    'can_return' => $canReturn,
                    'block_reason' => $canReturn
                        ? null
                        : ($blockedByOrder ? 'Linked purchase order is closed or cancelled.' : 'Fully returned.'),
                    'unit_price' => (string) $line->unit_price,
                ];
            })
            ->filter(fn (array $line): bool => $line['can_return'])
            ->values()
            ->all();
    }

    private function orderQuery(int $tenantId, ?int $organizationUnitId, ?int $supplierId, string $search): Builder
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'warehouseLocation', 'currency', 'lines.item', 'lines.variant', 'lines.uom'])
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'), fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('purchase_order_number', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
            }));
    }

    private function goodsReceiptQuery(int $tenantId, ?int $organizationUnitId, ?int $supplierId, string $search): Builder
    {
        return GoodsReceiptNote::query()
            ->with(['supplier', 'purchaseOrder', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine.order'])
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'), fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('grn_number', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
            }));
    }

    private function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $summary = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if (($model->{$field} ?? null) !== null) {
                $summary[$field] = $model->{$field};
            }
        }

        return $summary;
    }

    private function linkedPurchaseOrderClosed(GoodsReceiptNoteLine $line): bool
    {
        if (! $line->purchaseOrderLine instanceof PurchaseOrderLine) {
            return false;
        }

        $status = $this->statusValue($line->purchaseOrderLine->order?->status);

        return in_array($status, [PurchaseOrderStatus::Closed->value, PurchaseOrderStatus::Cancelled->value], true);
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status !== null ? (string) $status : null);
    }
}
