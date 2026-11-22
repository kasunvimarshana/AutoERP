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
