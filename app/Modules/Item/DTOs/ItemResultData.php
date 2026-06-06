<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;

final readonly class ItemResultData
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $code,
        public string $name,
        public ItemType $itemType,
        public TrackingType $trackingType,
        public CostingMethod $costingMethod,
        public bool $isStockable,
        public bool $isCombo,
        public bool $isActive,
    ) {}
}
