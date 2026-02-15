<?php

declare(strict_types=1);

namespace App\Events\Product;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productSku,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
