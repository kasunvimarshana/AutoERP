<?php

declare(strict_types=1);

namespace App\Events\Sales;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SalesOrder $order,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
