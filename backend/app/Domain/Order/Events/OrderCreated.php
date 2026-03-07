<?php

declare(strict_types=1);

namespace App\Domain\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

final class OrderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly int|string $tenantId,
        public readonly int|string $createdBy,
    ) {}
}
