<?php

declare(strict_types=1);

namespace App\Events\Procurement;

use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $approvedBy
    ) {}
}
