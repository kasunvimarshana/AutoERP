<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Payment $payment,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
