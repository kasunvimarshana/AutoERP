<?php

declare(strict_types=1);

namespace Modules\Purchase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Purchase\Models\BillPayment;

class BillPaymentRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BillPayment $billPayment
    ) {}
}
