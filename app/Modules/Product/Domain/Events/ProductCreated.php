<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productUuid,
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $type,
    ) {}
}
