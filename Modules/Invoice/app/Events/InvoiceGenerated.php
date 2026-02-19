<?php

declare(strict_types=1);

namespace Modules\Invoice\Events;

use App\Core\Events\BaseDomainEvent;
use Modules\Invoice\Models\Invoice;

/**
 * Invoice Generated Event
 *
 * Dispatched when an invoice is created.
 * Triggers:
 * - Email invoice to customer
 * - Update accounting system
 * - Generate PDF
 * - Update CRM
 */
class InvoiceGenerated extends BaseDomainEvent
{
    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly Invoice $invoice
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPayload(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'customer_id' => $this->invoice->customer_id,
            'total_amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date?->toIso8601String(),
        ];
    }
}
