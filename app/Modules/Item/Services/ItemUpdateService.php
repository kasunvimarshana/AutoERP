<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\DTOs\UpdateItemData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Validators\ItemValidationService;

final class ItemUpdateService
{
    public function __construct(private readonly ItemValidationService $validator) {}

    public function update(Item $item, UpdateItemData $data): Item
    {
        $this->validator->validateUpdate($item, $data);

        $attributes = [];
        foreach ([
            'code' => $data->code,
            'name' => $data->name,
            'item_type' => $data->itemType,
            'organization_unit_id' => $data->organizationUnitId,
            'item_category_id' => $data->itemCategoryId,
            'item_brand_id' => $data->itemBrandId,
            'sku' => $data->sku,
            'barcode' => $data->barcode,
            'description' => $data->description,
            'tracking_type' => $data->trackingType,
            'costing_method' => $data->costingMethod,
            'base_uom_id' => $data->baseUomId,
            'is_stockable' => $data->isStockable,
            'is_combo' => $data->isCombo,
            'is_active' => $data->isActive,
            'metadata' => $data->metadata,
        ] as $key => $value) {
            if (in_array($key, $data->provided, true)) {
                $attributes[$key] = $value;
            }
        }

        $resolvedType = $attributes['item_type'] ?? $item->item_type;
        $resolvedType = $resolvedType instanceof ItemType
            ? $resolvedType
            : ItemType::from((string) $resolvedType);
        if (in_array($resolvedType, [ItemType::Combo, ItemType::Package], true)) {
            $attributes['is_combo'] = true;
        } elseif (array_key_exists('item_type', $attributes) && ! in_array('is_combo', $data->provided, true)) {
            $attributes['is_combo'] = false;
        }

        $item->fill($attributes);
        $item->save();

        return $item->refresh();
    }

    public function setActive(Item $item, bool $isActive): Item
    {
        $item->is_active = $isActive;
        $item->save();

        return $item->refresh();
    }
}
