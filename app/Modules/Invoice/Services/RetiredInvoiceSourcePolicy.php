<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\Constants\RetiredInvoiceSource;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;

final class RetiredInvoiceSourcePolicy
{
    /**
     * Posted historical invoices may still move through settlement statuses,
     * but source-dependent lifecycle actions are unavailable after source
     * module removal.
     */
    public function assertTransitionAllowed(Invoice $invoice, InvoiceStatus $to): void
    {
        if (! $this->hasRetiredSource($invoice)) {
            return;
        }

        if (in_array($to, [InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid], true)) {
            return;
        }

        throw new InvalidArgumentException(
            'This historical invoice belongs to the retired Vehicle Rental module. '
            .'It remains available for audit and settlement, but it cannot be approved, posted, cancelled, voided, or reversed.',
        );
    }

    public function assertReversalAllowed(Invoice $invoice): void
    {
        if (! $this->hasRetiredSource($invoice)) {
            return;
        }

        throw new InvalidArgumentException(
            'This historical invoice belongs to the retired Vehicle Rental module and cannot be reversed because its source restoration workflow no longer exists.',
        );
    }

    public function hasRetiredSource(Invoice $invoice): bool
    {
        return $invoice->sources()
            ->whereIn('source_type', RetiredInvoiceSource::TYPES)
            ->exists();
    }
}
