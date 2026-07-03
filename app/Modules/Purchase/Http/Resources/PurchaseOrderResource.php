<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;

final class PurchaseOrderResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        $workflowStatus = $this->enumValue($this->status);
        $receiptStatus = (string) ($this->resource->getAttribute('receipt_status') ?? 'not_received');
        $invoiceStatus = (string) ($this->resource->getAttribute('invoice_status') ?? 'not_invoiced');
        $returnStatus = (string) ($this->resource->getAttribute('return_status') ?? 'not_returned');

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'purchase_order_number' => $this->purchase_order_number,
            'purchase_order_date' => $this->purchase_order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'status' => $workflowStatus,
            'status_label' => $this->statusLabel($workflowStatus),
            'workflow_status' => $workflowStatus,
            'workflow_status_label' => $this->statusLabel($workflowStatus),
            'receipt_status' => $receiptStatus,
            'receipt_status_label' => $this->statusLabel($receiptStatus),
            'invoice_status' => $invoiceStatus,
            'invoice_status_label' => $this->statusLabel($invoiceStatus),
            'return_status' => $returnStatus,
            'return_status_label' => $this->statusLabel($returnStatus),
            'capabilities' => $this->arrayAttribute('capabilities'),
            'supplier_type' => $this->supplier_type,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'adjustment_total' => (string) $this->adjustment_total,
            'header_increase_total' => (string) $this->header_increase_total,
            'header_decrease_total' => (string) $this->header_decrease_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->userSummary($this->createdBy)),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->userSummary($this->approvedBy)),
            'approved_at' => $this->approved_at?->toISOString(),
            'closed_by' => $this->whenLoaded('closedBy', fn () => $this->userSummary($this->closedBy)),
            'closed_at' => $this->closed_at?->toISOString(),
            'received_quantity' => (string) ($this->received_quantity ?? '0.000000'),
            'invoiced_quantity' => (string) ($this->invoiced_quantity ?? '0.000000'),
            'returned_quantity' => (string) ($this->returned_quantity ?? '0.000000'),
            'lines' => $this->whenLoaded('lines', fn () => PurchaseOrderLineResource::collection($this->lines)->resolve($request), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => PurchaseHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'related_documents' => $this->arrayAttribute('related_documents'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function userSummary(mixed $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->name ?? $user->full_name ?? $user->email ?? 'User #'.$user->getKey(),
            'email' => $user->email ?? null,
        ];
    }

}
