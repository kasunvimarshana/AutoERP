<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\Enums\ItemUnitRole;
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
        return DB::transaction(function () use ($item, $data): ItemUnit {
            $lockedItem = $this->lockItem($item);
            $this->assertOrdinaryUnitRole($data);
            $this->validator->validateUnit($lockedItem, $data);

            if ($data->isDefault) {
                $this->clearOtherDefaults($lockedItem);
            }

            return ItemUnit::query()->create([
                'tenant_id' => $lockedItem->tenant_id,
                'organization_unit_id' => $lockedItem->organization_unit_id,
                'item_id' => $lockedItem->getKey(),
                'uom_id' => $data->uomId,
                'unit_role' => $data->unitRole,
                'conversion_factor' => $this->math->normalize($data->conversionFactor),
                'is_default' => $data->isDefault,
                'is_active' => $data->isActive,
            ])->load('uom');
        });
    }

    public function assignInitial(Item $item, ItemUnitData $data): ItemUnit
    {
        if ($data->unitRole === ItemUnitRole::Base) {
            if ($data->isDefault && ! $data->isActive) {
                throw ValidationException::withMessages([
                    'is_default' => ['Default Unit must be active.'],
                    'is_active' => ['Inactive item units cannot be marked as Default Unit.'],
                ]);
            }
            if ($item->base_uom_id === null || (int) $item->base_uom_id !== $data->uomId) {
                throw ValidationException::withMessages([
                    'uom_id' => ['Base item unit must match the item Base UOM.'],
                ]);
            }
            if ($this->math->compare($data->conversionFactor, '1') !== 0) {
                throw ValidationException::withMessages([
                    'conversion_factor' => ['Base item unit conversion factor must be 1.'],
                ]);
            }

            return $this->syncBaseUnit($item, forceDefault: $data->isDefault);
        }

        return $this->assign($item, $data);
    }

    public function update(Item $item, ItemUnit $unit, ItemUnitData $data): ItemUnit
    {
        return DB::transaction(function () use ($item, $unit, $data): ItemUnit {
            $lockedItem = $this->lockItem($item);
            $this->assertBelongsToItem($lockedItem, $unit);
            $this->assertOrdinaryUnitRole($data);
            $this->assertNotBaseUnit($unit);
            $this->validator->validateUnit($lockedItem, $data, (int) $unit->getKey());

            if ($data->isDefault) {
                $this->clearOtherDefaults($lockedItem, (int) $unit->getKey());
            }

            $unit->fill([
                'uom_id' => $data->uomId,
                'unit_role' => $data->unitRole,
                'conversion_factor' => $this->math->normalize($data->conversionFactor),
                'is_default' => $data->isDefault,
                'is_active' => $data->isActive,
            ])->save();

            return $unit->refresh()->load('uom');
        });
    }

    public function delete(Item $item, ItemUnit $unit): void
    {
        DB::transaction(function () use ($item, $unit): void {
            $lockedItem = $this->lockItem($item);
            $this->assertBelongsToItem($lockedItem, $unit);
            $this->assertNotBaseUnit($unit);
            $unit->delete();
        });
    }

    public function syncBaseUnit(Item $item, bool $makeDefaultWhenNeeded = true, bool $forceDefault = false): ItemUnit
    {
        return DB::transaction(function () use ($item, $makeDefaultWhenNeeded, $forceDefault): ItemUnit {
            $lockedItem = $this->lockItem($item);
            if ($lockedItem->base_uom_id === null) {
                throw ValidationException::withMessages([
                    'base_uom_id' => ['A base UOM is required before synchronizing the base item unit.'],
                ]);
            }

            $units = ItemUnit::query()
                ->where('item_id', $lockedItem->getKey())
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            $baseUnit = $units->first(fn (ItemUnit $unit): bool => $unit->unit_role === ItemUnitRole::Base
                && (int) $unit->uom_id === (int) $lockedItem->base_uom_id);

            if (! $baseUnit instanceof ItemUnit) {
                $baseUnit = $units->first(fn (ItemUnit $unit): bool => $unit->unit_role === ItemUnitRole::Base);
            }

            if ($baseUnit instanceof ItemUnit) {
                $baseUnit->fill([
                    'uom_id' => $lockedItem->base_uom_id,
                    'unit_role' => ItemUnitRole::Base,
                    'conversion_factor' => '1.000000',
                    'is_active' => true,
                ])->save();
            } else {
                $baseUnit = ItemUnit::query()->create([
                    'tenant_id' => $lockedItem->tenant_id,
                    'organization_unit_id' => $lockedItem->organization_unit_id,
                    'item_id' => $lockedItem->getKey(),
                    'uom_id' => $lockedItem->base_uom_id,
                    'unit_role' => ItemUnitRole::Base,
                    'conversion_factor' => '1.000000',
                    'is_default' => false,
                    'is_active' => true,
                ]);
            }

            ItemUnit::query()
                ->where('item_id', $lockedItem->getKey())
                ->where('unit_role', ItemUnitRole::Base->value)
                ->whereKeyNot($baseUnit->getKey())
                ->delete();

            if ($forceDefault || ($makeDefaultWhenNeeded && ! $this->hasValidDefault($lockedItem))) {
                $this->clearOtherDefaults($lockedItem, (int) $baseUnit->getKey());
                $baseUnit->is_default = true;
            } elseif (! (bool) $baseUnit->is_default || $this->hasOtherValidDefault($lockedItem, (int) $baseUnit->getKey())) {
                $baseUnit->is_default = false;
            }

            $baseUnit->save();
            $this->repairDefaultInvariant($lockedItem);

            return $baseUnit->refresh()->load('uom');
        });
    }

    private function lockItem(Item $item): Item
    {
        return Item::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function clearOtherDefaults(Item $item, ?int $ignoreId = null): void
    {
        $query = ItemUnit::query()
            ->where('item_id', $item->getKey());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        $query->update(['is_default' => false]);
    }

    private function repairDefaultInvariant(Item $item): void
    {
        $defaults = ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->where('is_default', true)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        $keeper = $defaults->firstWhere('is_active', true);
        if (! $keeper instanceof ItemUnit) {
            ItemUnit::query()->where('item_id', $item->getKey())->update(['is_default' => false]);

            return;
        }

        ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->whereKeyNot($keeper->getKey())
            ->update(['is_default' => false]);
    }

    private function hasValidDefault(Item $item): bool
    {
        return ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->where('is_default', true)
            ->where('is_active', true)
            ->exists();
    }

    private function hasOtherValidDefault(Item $item, int $ignoreId): bool
    {
        return ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->whereKeyNot($ignoreId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->exists();
    }

    private function assertBelongsToItem(Item $item, ItemUnit $unit): void
    {
        if ((int) $unit->item_id !== (int) $item->getKey()) {
            throw new \InvalidArgumentException('Item unit does not belong to the item.');
        }
    }

    private function assertOrdinaryUnitRole(ItemUnitData $data): void
    {
        if ($data->unitRole === ItemUnitRole::Base) {
            throw ValidationException::withMessages([
                'unit_role' => ['Base item units are synchronized from the Base UOM workflow. Change the item Base UOM instead.'],
            ]);
        }
    }

    private function assertNotBaseUnit(ItemUnit $unit): void
    {
        $role = $unit->unit_role instanceof ItemUnitRole ? $unit->unit_role : ItemUnitRole::from((string) $unit->unit_role);
        if ($role === ItemUnitRole::Base) {
            throw ValidationException::withMessages([
                'unit_role' => ['Base item units are protected. Use the Base UOM change workflow instead.'],
            ]);
        }
    }
}
