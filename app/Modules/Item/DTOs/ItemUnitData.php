<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\ItemUnitRole;

final readonly class ItemUnitData
{
    public function __construct(
        public int $uomId,
        public ItemUnitRole $unitRole,
        public string $conversionFactor = '1.000000',
        public bool $isDefault = false,
        public bool $isActive = true,
    ) {}
}
