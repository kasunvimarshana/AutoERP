<?php

namespace App\Modules\POS\Events;

use App\Modules\POS\Models\Quotation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Quotation $quotation) {}
}
