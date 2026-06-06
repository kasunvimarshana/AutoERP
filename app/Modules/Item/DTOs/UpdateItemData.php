<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;

final readonly class UpdateItemData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?ItemType $itemType = null,
        public ?int $organizationUnitId = null,
        public ?int $itemCategoryId = null,
        public ?int $itemBrandId = null,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?string $description = null,
        public ?TrackingType $trackingType = null,
        public ?CostingMethod $costingMethod = null,
        public ?int $baseUomId = null,
        public ?bool $isStockable = null,
        public ?bool $isCombo = null,
        public ?bool $isActive = null,
        public ?array $metadata = null,
    ) {}
}
