<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Validators\ItemValidationService;

final class ItemCreationService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly ItemUnitService $units,
        private readonly ItemVariantService $variants,
        private readonly ItemBundleService $bundles,
        private readonly ItemPriceService $prices,
        private readonly ItemCodeService $codes,
        private readonly ItemUsageRuleService $usageRules,
    ) {}

    public function create(CreateItemData $data): Item
    {
        $this->validator->validateCreate($data);

        return DB::transaction(function () use ($data): Item {
            /** @var Item $item */
            $item = Item::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'item_category_id' => $data->itemCategoryId,
                'item_brand_id' => $data->itemBrandId,
                'code' => $data->code,
                'sku' => $data->sku,
                'barcode' => $data->barcode,
                'name' => $data->name,
                'description' => $data->description,
                'item_type' => $data->itemType,
                'tracking_type' => $data->trackingType,
                'costing_method' => $data->costingMethod,
                'base_uom_id' => $data->baseUomId,
                'default_tax_group_id' => $data->defaultTaxGroupId,
                'purchase_tax_group_id' => $data->purchaseTaxGroupId,
                'sales_tax_group_id' => $data->salesTaxGroupId,
                'is_stockable' => $data->isStockable,
                'is_combo' => in_array($data->itemType, [ItemType::Combo, ItemType::Package], true),
                'is_tax_exempt' => $data->isTaxExempt,
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ]);

            if ($item->base_uom_id !== null) {
                $this->units->syncBaseUnit($item);
            }

            foreach ($data->units as $unit) {
                $this->units->assignInitial($item, $unit);
            }

            foreach ($data->variants as $variant) {
                $this->variants->create($item, $variant);
            }

            foreach ($data->bundles as $bundle) {
                $this->bundles->addLine($item, $bundle);
            }

            foreach ($data->prices as $price) {
                $this->prices->create($item, $price);
            }

            foreach ($data->codes as $code) {
                $this->codes->create($item, $code);
            }

            foreach ($data->usageRules as $rule) {
                $this->usageRules->set($item, $rule);
            }

            return $item->refresh()->load([
                'category',
                'brand',
                'tenant.currency',
                'baseUom',
                'defaultTaxGroup',
                'purchaseTaxGroup',
                'salesTaxGroup',
                'units.uom',
                'variants',
                'bundleLines.childItem.category',
                'bundleLines.childItem.brand',
                'bundleLines.childVariant',
                'bundleLines.uom',
                'prices.variant',
                'prices.currency',
                'prices.uom',
                'codes.variant',
                'usageRules',
            ]);
        });
    }
}
