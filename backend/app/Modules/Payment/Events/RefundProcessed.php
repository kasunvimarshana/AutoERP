<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\PaymentRefund;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PaymentRefund $refund
    ) {}
}
