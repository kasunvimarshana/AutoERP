<?php

namespace App\Modules\POS\Events;

use App\Modules\POS\Models\SalesOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SalesOrder $salesOrder) {}
}
