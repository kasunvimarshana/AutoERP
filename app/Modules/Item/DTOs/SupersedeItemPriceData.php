<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

final readonly class SupersedeItemPriceData
{
    public function __construct(
        public ItemPriceData $price,
        public int $expectedVersion,
        public string $correctionReason,
    ) {}
}
