<?php

namespace App\Modules\Invoice\Events;

use App\Modules\Invoice\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRecorded
{
    use Dispatchable, SerializesModels;

    public Invoice $invoice;
    public float $amount;

    public function __construct(Invoice $invoice, float $amount)
    {
        $this->invoice = $invoice;
        $this->amount = $amount;
    }
}
