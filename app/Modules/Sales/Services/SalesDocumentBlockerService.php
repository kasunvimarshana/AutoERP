<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturn;

final class SalesDocumentBlockerService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesFulfilmentBalanceService $balances,
    ) {}

    /**
     * @return array{code: string, reason: string}|null
     */
    public function salesOrderCloseBlocker(SalesOrder $order): ?array
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if (! $line instanceof SalesOrderLine) {
                continue;
            }
            if ($this->positive($this->balances->remainingDeliverableForSalesOrderLine($line))) {
                return ['code' => 'remaining_deliverable', 'reason' => 'Sales order has remaining deliverable quantities.'];
            }
            if ($this->positive($this->balances->remainingInvoiceableForSalesOrderLine($line))) {
                return ['code' => 'remaining_invoiceable', 'reason' => 'Sales order has remaining invoiceable quantities.'];
            }
        }

        if ($this->hasUnresolvedInvoices((int) $order->getKey())) {
            return ['code' => 'unresolved_invoices', 'reason' => 'Sales order cannot be closed while customer invoices are unresolved.'];
        }

        return null;
    }

    /**
     * @return array{code: string, reason: string}|null
     */
    public function salesDeliveryReverseBlocker(SalesDelivery $delivery): ?array
    {
        $delivery->loadMissing('lines');
        foreach ($delivery->lines as $line) {
            if ($this->positive((string) $line->invoiced_quantity)) {
                return ['code' => 'has_invoiced_quantity', 'reason' => 'Sales deliveries with invoiced lines cannot be reversed.'];
            }
            if ($this->positive((string) $line->returned_quantity)) {
                return ['code' => 'has_returned_quantity', 'reason' => 'Sales deliveries with returned lines cannot be reversed.'];
            }
        }

        $returns = SalesReturn::query()
            ->where('source_type', 'sales_delivery')
            ->where('source_id', $delivery->getKey())
            ->where('status', '!=', SalesReturnStatus::Cancelled->value)
            ->count();

        return $returns > 0
            ? ['code' => 'unresolved_returns', 'reason' => 'Cannot reverse sales delivery while sales returns are unresolved or impacting.']
            : null;
    }

    /**
     * @return array{code: string, reason: string}|null
     */
    public function salesCreditNoteReverseBlocker(SalesCreditNote $note): ?array
    {
        $status = $this->statusValue($note->status);
        if (! in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true)) {
            return ['code' => 'not_posted', 'reason' => 'Only posted or allocated sales credit notes can be reversed.'];
        }
        if ($this->positive((string) $note->allocated_amount)) {
            return ['code' => 'has_allocations', 'reason' => 'Allocated sales credit notes must have allocations reversed first.'];
        }

        return null;
    }

    private function hasUnresolvedInvoices(int $salesOrderId): bool
    {
        $invoiceIds = SalesInvoiceLink::query()
            ->where('source_type', 'sales_order')
            ->where('source_id', $salesOrderId)
            ->pluck('invoice_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($invoiceIds === []) {
            return false;
        }

        return Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Approved->value])
            ->exists();
    }

    private function positive(string $amount): bool
    {
        return $this->math->compare($amount, '0.000000') > 0;
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
