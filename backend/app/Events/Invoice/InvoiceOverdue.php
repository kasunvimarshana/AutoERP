<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Modules\Invoice\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $daysOverdue,
        public readonly int $tenantId
    ) {}
}
