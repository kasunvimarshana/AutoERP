<?php

declare(strict_types=1);

namespace App\Events\Product;

use App\Modules\Product\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly array $changes,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
