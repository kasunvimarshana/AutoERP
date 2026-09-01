<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Item\Enums\ItemPriceType;

final readonly class BatchPriceData
{
    public function __construct(
        public int $batchId,
        public ItemPriceType $priceType,
        public string $amount,
        public int $currencyId,
        public int $uomId,
        public ?int $organizationUnitId,
        public string $effectiveFrom,
        public ?string $effectiveTo = null,
    ) {}
}
