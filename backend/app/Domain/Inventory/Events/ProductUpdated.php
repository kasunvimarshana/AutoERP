<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;

/**
 * Domain event fired after a product record is updated.
 */
final class ProductUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly array $changedAttributes,
        public readonly int|string $tenantId,
        public readonly int|string $updatedBy,
    ) {}
}
