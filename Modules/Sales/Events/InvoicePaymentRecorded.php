<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoicePayment;

class InvoicePaymentRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public InvoicePayment $payment
    ) {}
}
