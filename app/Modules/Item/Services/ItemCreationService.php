<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemCode;
use Modules\Item\Validators\ItemValidationService;

final class ItemCreationService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly ItemUnitService $units,
        private readonly ItemVariantService $variants,
        private readonly ItemBundleService $bundles,
        private readonly ItemPriceService $prices,
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
                'is_stockable' => $data->isStockable,
                'is_combo' => $data->isCombo ?? in_array($data->itemType, [ItemType::Combo, ItemType::Package], true),
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ]);

            foreach ($data->units as $unit) {
                $this->units->assign($item, $unit);
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
                $this->createCode($item, $code);
            }

            foreach ($data->usageRules as $rule) {
                $this->usageRules->set($item, $rule);
            }

            return $item->refresh()->load([
                'units',
                'variants',
                'bundleLines',
                'prices',
                'codes',
                'usageRules',
            ]);
        });
    }

    private function createCode(Item $item, ItemCodeData $data): ItemCode
    {
        $this->validator->validateCode($item, $data);

        return ItemCode::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'item_variant_id' => $data->itemVariantId,
            'code_type' => $data->codeType,
            'code' => $data->code,
            'party_type' => $data->partyType,
            'party_id' => $data->partyId,
            'is_primary' => $data->isPrimary,
        ]);
    }
}
