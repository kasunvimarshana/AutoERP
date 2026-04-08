<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
        public readonly array $changes,
    ) {}
}
