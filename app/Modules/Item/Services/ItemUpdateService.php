<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\UpdateItemData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Validators\ItemValidationService;
use Modules\UOM\Services\UomConversionService;

final class ItemUpdateService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
        private readonly UomConversionService $uomConversions,
        private readonly ItemUnitService $units,
    ) {}

    public function update(Item $item, UpdateItemData $data): Item
    {
        $this->validator->validateUpdate($item, $data);
        $baseUomChanged = in_array('base_uom_id', $data->provided, true)
            && (int) ($data->baseUomId ?? 0) !== (int) ($item->base_uom_id ?? 0);
        $unitFactor = $baseUomChanged ? $this->directEditUnitFactor($item, $data->baseUomId) : null;

        return DB::transaction(function () use ($item, $data, $baseUomChanged, $unitFactor): Item {
            $item = Item::query()
                ->where('tenant_id', $item->tenant_id)
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $attributes = [];
            foreach ([
                'code' => $data->code,
                'name' => $data->name,
                'item_type' => $data->itemType,
                'item_category_id' => $data->itemCategoryId,
                'item_brand_id' => $data->itemBrandId,
                'sku' => $data->sku,
                'barcode' => $data->barcode,
                'description' => $data->description,
                'tracking_type' => $data->trackingType,
                'costing_method' => $data->costingMethod,
                'base_uom_id' => $data->baseUomId,
                'standard_price' => $data->standardPrice === null ? null : $this->math->normalize($data->standardPrice),
                'default_tax_group_id' => $data->defaultTaxGroupId,
                'purchase_tax_group_id' => $data->purchaseTaxGroupId,
                'sales_tax_group_id' => $data->salesTaxGroupId,
                'is_stockable' => $data->isStockable,
                'is_combo' => $data->isCombo,
                'is_tax_exempt' => $data->isTaxExempt,
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
            $attributes['is_combo'] = in_array($resolvedType, [ItemType::Combo, ItemType::Package], true);
            if (! in_array('item_type', $data->provided, true) && ! in_array('is_combo', $data->provided, true)) {
                unset($attributes['is_combo']);
            }

            $item->fill($attributes);
            $item->save();

            if ($baseUomChanged) {
                $this->synchronizeUnusedItemUnits($item, $data->baseUomId, $unitFactor);
            }

            return $item->refresh();
        });
    }

    public function setActive(Item $item, bool $isActive): Item
    {
        $item->is_active = $isActive;
        $item->save();

        return $item->refresh();
    }

    private function directEditUnitFactor(Item $item, ?int $newBaseUomId): ?string
    {
        $nonBaseUnits = $item->units()->where('unit_role', '!=', ItemUnitRole::Base->value)->get();
        if ($nonBaseUnits->isEmpty()) {
            return null;
        }
        if ($newBaseUomId === null) {
            throw new InvalidArgumentException('Base UOM cannot be cleared while item units are configured.');
        }
        if ($item->base_uom_id === null) {
            return '1.000000';
        }

        $newBaseUnit = $nonBaseUnits->firstWhere('uom_id', $newBaseUomId);
        if ($newBaseUnit instanceof ItemUnit && ! $this->math->isZero((string) $newBaseUnit->conversion_factor)) {
            return $this->math->div('1', (string) $newBaseUnit->conversion_factor);
        }

        $result = $this->uomConversions->getConversionFactor(
            (int) $item->base_uom_id,
            $newBaseUomId,
            (int) $item->tenant_id,
        );
        if ($result->isFailure()) {
            throw new InvalidArgumentException('A UOM conversion is required to update configured item units.');
        }

        return $this->math->normalize((string) $result->valueOrFail());
    }

    private function synchronizeUnusedItemUnits(Item $item, ?int $newBaseUomId, ?string $factor): void
    {
        if ($newBaseUomId === null) {
            ItemUnit::query()->where('item_id', $item->getKey())->where('unit_role', ItemUnitRole::Base->value)->delete();

            return;
        }

        $units = ItemUnit::query()->where('item_id', $item->getKey())->lockForUpdate()->get();
        foreach ($units as $unit) {
            if ($unit->unit_role === ItemUnitRole::Base) {
                continue;
            }
            if ($factor !== null) {
                $unit->conversion_factor = (int) $unit->uom_id === $newBaseUomId
                    ? '1.000000'
                    : $this->math->mul((string) $unit->conversion_factor, $factor);
                $unit->save();
            }
        }

        $this->units->syncBaseUnit($item);
    }
}
