<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Validators\ItemValidationService;

final class ItemUnitService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
    ) {}

    public function assign(Item $item, ItemUnitData $data): ItemUnit
    {
        $this->validator->validateUnit($item, $data);

        if ($data->isDefault) {
            ItemUnit::query()
                ->where('item_id', $item->getKey())
                ->where('unit_role', $data->unitRole->value)
                ->update(['is_default' => false]);
        }

        return ItemUnit::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'uom_id' => $data->uomId,
            'unit_role' => $data->unitRole,
            'conversion_factor' => $this->math->normalize($data->conversionFactor),
            'is_default' => $data->isDefault,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(Item $item, ItemUnit $unit, ItemUnitData $data): ItemUnit
    {
        $this->assertBelongsToItem($item, $unit);
        $this->validator->validateUnit($item, $data, (int) $unit->getKey());
        $this->clearOtherDefaults($item, $data, (int) $unit->getKey());

        $unit->fill([
            'uom_id' => $data->uomId,
            'unit_role' => $data->unitRole,
            'conversion_factor' => $this->math->normalize($data->conversionFactor),
            'is_default' => $data->isDefault,
            'is_active' => $data->isActive,
        ])->save();

        return $unit->refresh()->load('uom');
    }

    public function delete(Item $item, ItemUnit $unit): void
    {
        $this->assertBelongsToItem($item, $unit);
        $unit->delete();
    }

    private function clearOtherDefaults(Item $item, ItemUnitData $data, ?int $ignoreId = null): void
    {
        if (! $data->isDefault) {
            return;
        }

        $query = ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->where('unit_role', $data->unitRole->value);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        $query->update(['is_default' => false]);
    }

    private function assertBelongsToItem(Item $item, ItemUnit $unit): void
    {
        if ((int) $unit->item_id !== (int) $item->getKey()) {
            throw new \InvalidArgumentException('Item unit does not belong to the item.');
        }
    }
}
