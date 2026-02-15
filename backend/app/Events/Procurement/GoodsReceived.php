<?php

declare(strict_types=1);

namespace App\Events\Procurement;

use App\Modules\Procurement\Models\PurchaseReceipt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoodsReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PurchaseReceipt $purchaseReceipt,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
