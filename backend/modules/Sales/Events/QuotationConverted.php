<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;

class QuotationConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public SalesOrder $salesOrder
    ) {}
}
