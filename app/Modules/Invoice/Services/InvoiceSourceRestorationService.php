<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Services\PurchaseInvoiceQuantityUpdater;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Services\SalesInvoiceQuantityUpdater;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;

final class InvoiceSourceRestorationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesInvoiceQuantityUpdater $salesQuantities,
        private readonly PurchaseInvoiceQuantityUpdater $purchaseQuantities,
    ) {}

    public function restore(Invoice $invoice): void
    {
        $invoice->loadMissing('sourceLines');
        $hasSalesIntegration = SalesInvoiceLink::query()
            ->where('invoice_id', $invoice->getKey())
            ->where('status', 'active')
            ->exists();
        $hasPurchaseIntegration = PurchaseInvoiceLink::query()
            ->where('invoice_id', $invoice->getKey())
            ->where('status', 'active')
            ->exists();

        $salesLineQuantities = [];
        $purchaseLineQuantities = [];
        $salesDeliveryIds = [];
        $goodsReceiptIds = [];

        foreach ($invoice->sourceLines as $sourceLine) {
            $lineKey = $sourceLine->source_line_type.':'.$sourceLine->source_line_id;
            $quantity = (string) $sourceLine->invoiced_quantity;

            if ($hasSalesIntegration && in_array($sourceLine->source_line_type, [
                'sales_delivery_line',
                'sales_order_line',
            ], true)) {
                $salesLineQuantities[$lineKey] = $this->math->add(
                    $salesLineQuantities[$lineKey] ?? '0.000000',
                    $quantity,
                );
                if ($sourceLine->source_type === 'sales_delivery') {
                    $salesDeliveryIds[] = (int) $sourceLine->source_id;
                }

                continue;
            }

            if ($hasPurchaseIntegration && in_array($sourceLine->source_line_type, [
                'goods_receipt_note_line',
                'purchase_order_line',
            ], true)) {
                $purchaseLineQuantities[$lineKey] = $this->math->add(
                    $purchaseLineQuantities[$lineKey] ?? '0.000000',
                    $quantity,
                );
                if ($sourceLine->source_type === 'goods_receipt_note') {
                    $goodsReceiptIds[] = (int) $sourceLine->source_id;
                }
            }
        }

        if ($salesLineQuantities !== []) {
            $this->salesQuantities->reverse(
                $salesLineQuantities,
                $this->salesDeliveries($invoice, $salesDeliveryIds),
            );
        }

        if ($purchaseLineQuantities !== []) {
            $this->purchaseQuantities->reverse(
                $purchaseLineQuantities,
                $this->goodsReceipts($invoice, $goodsReceiptIds),
            );
        }

        SalesInvoiceLink::query()
            ->where('invoice_id', $invoice->getKey())
            ->update(['status' => 'cancelled']);
        PurchaseInvoiceLink::query()
            ->where('invoice_id', $invoice->getKey())
            ->update(['status' => 'cancelled']);
        VehicleServiceInvoiceLink::query()
            ->where('invoice_id', $invoice->getKey())
            ->update(['status' => 'cancelled']);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, SalesDelivery>
     */
    private function salesDeliveries(Invoice $invoice, array $ids): Collection
    {
        return $this->scope(SalesDelivery::query(), $invoice)
            ->whereIn('id', array_values(array_unique($ids)))
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, GoodsReceiptNote>
     */
    private function goodsReceipts(Invoice $invoice, array $ids): Collection
    {
        return $this->scope(GoodsReceiptNote::query(), $invoice)
            ->whereIn('id', array_values(array_unique($ids)))
            ->lockForUpdate()
            ->get();
    }

    private function scope(Builder $query, Invoice $invoice): Builder
    {
        $query->where('tenant_id', $invoice->tenant_id);

        return $invoice->organization_unit_id === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $invoice->organization_unit_id);
    }
}
