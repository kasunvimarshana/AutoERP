<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

final readonly class ItemBundleData
{
    public function __construct(
        public int $childItemId,
        public string $quantity,
        public string $lineType,
        public ?int $childVariantId = null,
        public ?int $uomId = null,
        public bool $isRequired = true,
        public int $sortOrder = 0,
        public string $unitCost = '0.000000',
        public bool $usesJobSupervisor = false,
    ) {}
}
