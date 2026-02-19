<?php

declare(strict_types=1);

namespace Modules\Purchase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Purchase\Models\Bill;

class BillCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Bill $bill
    ) {}
}
