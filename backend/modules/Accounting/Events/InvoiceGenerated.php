<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\Invoice;

class InvoiceGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}
}
