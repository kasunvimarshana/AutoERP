<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\Invoice;
use Modules\Accounting\Models\Payment;

class PaymentAllocated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public Invoice $invoice,
        public float $amount
    ) {}
}
