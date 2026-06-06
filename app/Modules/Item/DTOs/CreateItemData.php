<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;

final readonly class CreateItemData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<ItemUnitData>  $units
     * @param  list<ItemVariantData>  $variants
     * @param  list<ItemBundleData>  $bundles
     * @param  list<ItemPriceData>  $prices
     * @param  list<ItemCodeData>  $codes
     * @param  list<ItemUsageRuleData>  $usageRules
     */
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public ItemType $itemType,
        public ?int $organizationUnitId = null,
        public ?int $itemCategoryId = null,
        public ?int $itemBrandId = null,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?string $description = null,
        public TrackingType $trackingType = TrackingType::None,
        public CostingMethod $costingMethod = CostingMethod::None,
        public ?int $baseUomId = null,
        public bool $isStockable = false,
        public ?bool $isCombo = null,
        public bool $isActive = true,
        public ?array $metadata = null,
        public array $units = [],
        public array $variants = [],
        public array $bundles = [],
        public array $prices = [],
        public array $codes = [],
        public array $usageRules = [],
    ) {}
}
