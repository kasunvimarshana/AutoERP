<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Entities\Invoice;

/**
 * Invoice Created Event
 *
 * Dispatched when a new invoice is created.
 */
class InvoiceCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {}
}
