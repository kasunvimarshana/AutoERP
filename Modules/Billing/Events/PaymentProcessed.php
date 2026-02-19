<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\SubscriptionPayment;

class PaymentProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SubscriptionPayment $payment
    ) {}
}
