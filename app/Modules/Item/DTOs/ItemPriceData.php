<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\DTOs;

use Modules\Item\Enums\ItemPriceType;

final readonly class ItemPriceData
{
    public function __construct(
        public ItemPriceType $priceType,
        public string $amount,
        public int $currencyId,
        public int $uomId,
        public ?int $organizationUnitId,
        public string $effectiveFrom,
        public ?int $itemVariantId = null,
        public ?string $effectiveTo = null,
    ) {}
}
