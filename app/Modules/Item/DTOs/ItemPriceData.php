<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\ItemPriceType;

final readonly class ItemPriceData
{
    public function __construct(
        public ItemPriceType $priceType,
        public string $amount,
        public ?int $itemVariantId = null,
        public ?int $currencyId = null,
        public ?int $uomId = null,
        public ?string $effectiveFrom = null,
        public ?string $effectiveTo = null,
        public bool $isActive = true,
    ) {}
}
