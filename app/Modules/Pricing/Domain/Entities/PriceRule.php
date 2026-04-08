<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use DateTimeInterface;

class PriceRule
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $priceListId,
        public readonly ?string $productId,
        public readonly ?string $categoryId,
        public readonly ?string $variantId,
        public readonly float $minQty,
        public readonly float $price,
        public readonly float $discountPercent,
        public readonly ?DateTimeInterface $startDate,
        public readonly ?DateTimeInterface $endDate,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
