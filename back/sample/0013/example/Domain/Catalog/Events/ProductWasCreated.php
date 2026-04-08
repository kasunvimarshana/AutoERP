<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Shared\Domain\ValueObjects\Uuid;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Catalog\ValueObjects\Money;

final class ProductWasCreated
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Uuid        $productId,
        public readonly ProductName $name,
        public readonly Money       $price,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
