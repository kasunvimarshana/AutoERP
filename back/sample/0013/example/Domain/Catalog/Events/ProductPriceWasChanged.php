<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Shared\Domain\ValueObjects\Uuid;
use App\Domain\Catalog\ValueObjects\Money;

final class ProductPriceWasChanged
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Uuid  $productId,
        public readonly Money $oldPrice,
        public readonly Money $newPrice,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
