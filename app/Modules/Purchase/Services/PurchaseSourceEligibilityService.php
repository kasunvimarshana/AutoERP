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
            ->whereHas('lines', fn (Builder $query) => $query->whereRaw('remaining_receivable_quantity > 0'))
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
            ->whereHas('lines', fn (Builder $query) => $query->whereRaw('(ordered_quantity - cancelled_quantity - invoiced_quantity) > 0'))
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
            ->whereHas('lines', fn (Builder $query) => $query->whereRaw('(accepted_quantity - invoiced_quantity) > 0'))
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
            ->whereHas('lines', fn (Builder $query) => $query->whereRaw('(accepted_quantity - returned_quantity) > 0'))
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
        return $order->lines
            ->filter(fn (PurchaseOrderLine $line): bool => $this->math->compare((string) $line->remaining_receivable_quantity, '0.000000') > 0)
            ->values()
            ->map(fn (PurchaseOrderLine $line): array => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'item_id' => $line->item_id,
                'item' => $this->summary($line->item, ['code', 'name', 'sku']),
                'item_variant_id' => $line->item_variant_id,
                'item_variant' => $this->summary($line->variant, ['code', 'name', 'sku']),
                'uom_id' => $line->uom_id,
                'uom' => $this->summary($line->uom, ['code', 'name', 'symbol']),
                'ordered_quantity' => (string) $line->ordered_quantity,
                'received_quantity' => (string) $line->received_quantity,
                'remaining_quantity' => (string) $line->remaining_receivable_quantity,
                'unit_price' => (string) $line->unit_price,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function invoiceableOrderLines(PurchaseOrder $order): array
    {
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
        return $grn->lines
            ->map(function (GoodsReceiptNoteLine $line): array {
                $remaining = $this->balances->remainingInvoiceableForGoodsReceiptLine($line);

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
                    'can_invoice' => $this->math->compare($remaining, '0.000000') > 0,
                    'block_reason' => $this->math->compare($remaining, '0.000000') > 0 ? null : 'Fully invoiced through linked procurement quantity.',
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
        return $grn->lines
            ->map(function (GoodsReceiptNoteLine $line): array {
                $remaining = $this->balances->remainingReturnableForGoodsReceiptLine($line);

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
                    'can_return' => $this->math->compare($remaining, '0.000000') > 0,
                    'block_reason' => $this->math->compare($remaining, '0.000000') > 0 ? null : 'Fully returned.',
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
            ->with(['supplier', 'purchaseOrder', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine'])
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
}
