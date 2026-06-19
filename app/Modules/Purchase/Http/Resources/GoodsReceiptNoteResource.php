<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Purchase\Services\PurchaseDocumentCapabilityService;
use Modules\Purchase\Services\PurchaseProcurementBalanceService;

final class GoodsReceiptNoteResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'grn_number' => $this->grn_number,
            'received_date' => $this->received_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'workflow_status' => $this->enumValue($this->status),
            'workflow_status_label' => $this->statusLabel($this->status),
            'invoice_status' => $this->invoiceStatus(),
            'invoice_status_label' => $this->statusLabel($this->invoiceStatus()),
            'return_status' => $this->returnStatus(),
            'return_status_label' => $this->statusLabel($this->returnStatus()),
            'capabilities' => $this->capabilities(),
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => $this->summary($this->purchaseOrder, ['purchase_order_number', 'status'])),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'posted_at' => $this->posted_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'received_quantity' => (string) $line->received_quantity,
                'accepted_quantity' => (string) $line->accepted_quantity,
                'rejected_quantity' => (string) $line->rejected_quantity,
                'invoiced_quantity' => (string) $line->invoiced_quantity,
                'returned_quantity' => (string) $line->returned_quantity,
                'remaining_quantity' => (string) $line->remaining_quantity,
                'remaining_invoiceable_quantity' => app(PurchaseProcurementBalanceService::class)->remainingInvoiceableForGoodsReceiptLine($line),
                'remaining_returnable_quantity' => app(PurchaseProcurementBalanceService::class)->remainingReturnableForGoodsReceiptLine($line),
                'unit_price' => (string) $line->unit_price,
                'line_subtotal' => (string) $line->line_subtotal,
                'tax_group_id' => $line->tax_group_id,
                'line_total' => (string) $line->line_total,
                'status' => $this->enumValue($line->status),
            ])->all(), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => PurchaseHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function invoiceStatus(): string
    {
        return app(PurchaseProcurementBalanceService::class)->goodsReceiptInvoiceStatus($this->loadedLines());
    }

    private function returnStatus(): string
    {
        return app(PurchaseProcurementBalanceService::class)->goodsReceiptReturnStatus($this->loadedLines());
    }

    private function loadedLines(): Collection
    {
        if (! $this->resource->relationLoaded('lines')) {
            $this->resource->load('lines.purchaseOrderLine');
        }

        $lines = $this->whenLoaded('lines');

        return $lines instanceof Collection ? $lines : collect();
    }

    /**
     * @return array<string, mixed>
     */
    private function capabilities(): array
    {
        return app(PurchaseDocumentCapabilityService::class)->forGoodsReceipt($this->resource);
    }

}
