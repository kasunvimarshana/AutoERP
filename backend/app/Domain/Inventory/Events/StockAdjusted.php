<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;

/**
 * Domain event fired whenever the stock level for a product changes.
 *
 * A positive `delta` represents a stock increase (receipt / return).
 * A negative `delta` represents a stock decrease (sale / adjustment).
 */
final class StockAdjusted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int $delta,
        public readonly int $previousQuantity,
        public readonly int $newQuantity,
        public readonly string $reason,
        public readonly int|string $tenantId,
        public readonly int|string|null $adjustedBy = null,
    ) {}
}
