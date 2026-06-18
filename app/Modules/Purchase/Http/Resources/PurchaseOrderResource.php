<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Services\PurchaseRelatedDocumentService;
use Modules\Purchase\Services\PurchaseProcurementBalanceService;

final class PurchaseOrderResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        $workflowStatus = $this->enumValue($this->status);
        $receiptStatus = $this->receiptStatus();
        $invoiceStatus = $this->invoiceStatus();
        $returnStatus = $this->returnStatus();

        return [
            'id' => (int) $this->getKey(),
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
            'capabilities' => $this->capabilities(),
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
            'related_documents' => $request->routeIs('api.v1.purchase.orders.show')
                ? app(PurchaseRelatedDocumentService::class)->forPurchaseOrder($this->resource)
                : [
                    'goods_receipts' => [],
                    'supplier_invoices' => [],
                    'payments' => [],
                    'returns' => [],
                    'debit_notes' => [],
                ],
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

    private function receiptStatus(): string
    {
        return app(PurchaseProcurementBalanceService::class)->receiptStatus($this->loadedLines());
    }

    private function invoiceStatus(): string
    {
        return app(PurchaseProcurementBalanceService::class)->purchaseOrderInvoiceStatus($this->loadedLines());
    }

    private function returnStatus(): string
    {
        return app(PurchaseProcurementBalanceService::class)->purchaseOrderReturnStatus($this->loadedLines());
    }

    /**
     * @return array<string, bool>
     */
    private function capabilities(): array
    {
        $workflow = $this->status;
        $hasReceivedOrInvoiced = $this->compare($this->sumLines('received_quantity'), '0.000000') > 0
            || $this->compare($this->sumLines('invoiced_quantity'), '0.000000') > 0;
        $remainingReceivable = '0.000000';
        $remainingInvoiceable = '0.000000';
        $remainingReturnable = '0.000000';
        $balances = app(PurchaseProcurementBalanceService::class);
        foreach ($this->loadedLines() as $line) {
            $remainingReceivable = $this->add($remainingReceivable, $balances->remainingReceivableForPurchaseOrderLine($line));
            $remainingInvoiceable = $this->add($remainingInvoiceable, $balances->remainingInvoiceableForPurchaseOrderLine($line));
            $remainingReturnable = $this->add($remainingReturnable, $balances->remainingReturnableForPurchaseOrderLine($line));
        }
        $hasReceivable = $this->compare($remainingReceivable, '0.000000') > 0;
        $hasInvoiceable = $this->compare($remainingInvoiceable, '0.000000') > 0;
        $hasReturnable = $this->compare($remainingReturnable, '0.000000') > 0;
        $openWorkflow = in_array($workflow, [
            PurchaseOrderStatus::Approved,
        ], true);

        return [
            'can_edit' => $workflow === PurchaseOrderStatus::Draft,
            'can_submit' => $workflow === PurchaseOrderStatus::Draft,
            'can_approve' => $workflow === PurchaseOrderStatus::PendingApproval,
            'can_receive' => $openWorkflow && $hasReceivable,
            'can_invoice' => $openWorkflow && $hasInvoiceable,
            'can_return' => $openWorkflow && $hasReturnable,
            'can_close' => $openWorkflow && ! $hasReceivable && ! $hasInvoiceable,
            'can_cancel' => in_array($workflow, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::PendingApproval,
                PurchaseOrderStatus::Approved,
            ], true) && ! $hasReceivedOrInvoiced,
            'can_delete' => $workflow === PurchaseOrderStatus::Draft && ! $hasReceivedOrInvoiced,
        ];
    }

    private function loadedLines(): Collection
    {
        if (! $this->resource->relationLoaded('lines')) {
            $this->resource->load('lines');
        }

        $lines = $this->whenLoaded('lines');

        return $lines instanceof Collection ? $lines : collect();
    }

    private function sumLines(string $column): string
    {
        $total = '0.000000';
        foreach ($this->loadedLines() as $line) {
            $total = $this->add($total, (string) ($line->{$column} ?? '0.000000'));
        }

        return $total;
    }
}
