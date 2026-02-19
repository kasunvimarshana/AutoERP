<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\Order;

class OrderCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}
