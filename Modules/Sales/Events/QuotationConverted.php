<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Quotation;

class QuotationConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public Order $order
    ) {}
}
