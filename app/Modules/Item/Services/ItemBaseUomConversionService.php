<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationLine;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Item\Enums\ItemBaseUomRevisionStatus;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBaseUomRevision;
use Modules\Item\Models\ItemUnit;
use Modules\UOM\Services\UomConversionService;
use Throwable;

final class ItemBaseUomConversionService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly ItemBaseUomConversionValidator $validator,
        private readonly UomConversionService $uomConversions,
        private readonly ItemUnitService $units,
    ) {}

    public function apply(
        Item $item,
        int $newBaseUomId,
        ?string $providedFactor,
        ?string $effectiveAt,
        ?string $reason,
        ?int $userId,
    ): ItemBaseUomRevision {
        $validation = $this->validator->assertValid($item, $newBaseUomId, $providedFactor, $effectiveAt);
        $factor = (string) $validation['conversion_factor'];
        $revision = ItemBaseUomRevision::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'old_base_uom_id' => $item->base_uom_id,
            'new_base_uom_id' => $newBaseUomId,
            'conversion_factor' => $factor,
            'effective_at' => $validation['effective_at'],
            'reason' => $reason,
            'status' => ItemBaseUomRevisionStatus::Validated,
            'validation_summary' => $this->validationSummary($validation),
            'created_by' => $userId,
        ]);

        try {
            return DB::transaction(function () use ($item, $newBaseUomId, $providedFactor, $effectiveAt, $factor, $revision, $userId): ItemBaseUomRevision {
                $lockedItem = Item::query()
                    ->where('tenant_id', $item->tenant_id)
                    ->whereKey($item->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                if ((int) $lockedItem->base_uom_id !== (int) $revision->old_base_uom_id) {
                    throw new \InvalidArgumentException('Item base UOM changed after the preview. Refresh and preview again.');
                }

                $validation = $this->validator->assertValid($lockedItem, $newBaseUomId, $providedFactor, $effectiveAt);
                if ($this->math->compare((string) $validation['conversion_factor'], $factor) !== 0) {
                    throw new \InvalidArgumentException('Conversion factor changed after validation. Preview the conversion again.');
                }

                $balances = $this->scope(InventoryStockBalance::query(), $lockedItem)->lockForUpdate()->get();
                foreach ($balances as $balance) {
                    $balance->base_uom_id = $newBaseUomId;
                    foreach ([
                        'quantity_on_hand',
                        'quantity_reserved',
                        'quantity_allocated',
                        'quantity_available',
                        'quantity_returned',
                        'quantity_in_transit',
                        'quantity_damaged',
                        'quantity_quarantine',
                        'quantity_expired',
                        'quantity_scrapped',
                    ] as $column) {
                        $balance->{$column} = $this->math->mul((string) $balance->{$column}, $factor);
                    }
                    $balance->average_cost = $this->math->div((string) $balance->average_cost, $factor);
                    $balance->save();
                }

                $reservations = $this->scope(InventoryReservation::query(), $lockedItem)
                    ->whereIn('status', ['active', 'partially_allocated'])
                    ->lockForUpdate()
                    ->get();
                foreach ($reservations as $reservation) {
                    $reservation->base_uom_id = $newBaseUomId;
                    $reservation->conversion_factor = $this->math->mul(
                        (string) $reservation->conversion_factor,
                        $factor,
                    );
                    foreach (['quantity_reserved', 'quantity_allocated', 'quantity_released', 'quantity_remaining'] as $column) {
                        $reservation->{$column} = $this->math->mul((string) $reservation->{$column}, $factor);
                    }
                    $reservation->save();
                }

                $allocations = $this->scope(InventoryAllocation::query(), $lockedItem)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();
                foreach ($allocations as $allocation) {
                    $allocation->base_uom_id = $newBaseUomId;
                    $allocation->conversion_factor = $this->math->mul(
                        (string) $allocation->conversion_factor,
                        $factor,
                    );
                    foreach (['quantity_allocated', 'quantity_issued', 'quantity_reversed', 'quantity_released', 'quantity_remaining'] as $column) {
                        $allocation->{$column} = $this->math->mul((string) $allocation->{$column}, $factor);
                    }
                    $allocation->save();
                }

                $allocationLines = InventoryAllocationLine::query()
                    ->whereIn('allocation_id', $allocations->modelKeys())
                    ->lockForUpdate()
                    ->get();
                foreach ($allocationLines as $allocationLine) {
                    foreach (['quantity_allocated', 'quantity_issued', 'quantity_reversed', 'quantity_released', 'quantity_remaining'] as $column) {
                        $allocationLine->{$column} = $this->math->mul((string) $allocationLine->{$column}, $factor);
                    }
                    $allocationLine->save();
                }

                $layers = $this->scope(InventoryValuationLayer::query(), $lockedItem)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->get();
                foreach ($layers as $layer) {
                    $layer->base_uom_id = $newBaseUomId;
                    $layer->original_quantity = $this->math->mul((string) $layer->original_quantity, $factor);
                    $layer->remaining_quantity = $this->math->mul((string) $layer->remaining_quantity, $factor);
                    $layer->unit_cost = $this->math->div((string) $layer->unit_cost, $factor);
                    $layer->save();
                }

                $consumptions = InventoryValuationConsumption::query()
                    ->whereNull('reversed_by_movement_id')
                    ->whereHas('valuationLayer', fn ($query) => $this->scope($query, $lockedItem))
                    ->lockForUpdate()
                    ->get();
                foreach ($consumptions as $consumption) {
                    $consumption->quantity_consumed = $this->math->mul((string) $consumption->quantity_consumed, $factor);
                    $consumption->unit_cost = $this->math->div((string) $consumption->unit_cost, $factor);
                    $consumption->save();
                }

                $this->convertItemUnits($lockedItem, $newBaseUomId, $factor);
                if ($lockedItem->standard_price !== null) {
                    $lockedItem->standard_price = $this->math->div((string) $lockedItem->standard_price, $factor);
                }
                $lockedItem->base_uom_id = $newBaseUomId;
                $lockedItem->save();

                $revision->fill([
                    'status' => ItemBaseUomRevisionStatus::Applied,
                    'applied_by' => $userId,
                    'applied_at' => now(),
                    'validation_summary' => $this->validationSummary($validation),
                ])->save();

                return $revision->refresh()->load(['item.baseUom', 'oldBaseUom', 'newBaseUom']);
            });
        } catch (Throwable $exception) {
            $revision->forceFill([
                'status' => ItemBaseUomRevisionStatus::Failed,
                'validation_summary' => [
                    ...((array) $revision->validation_summary),
                    'failure' => $exception->getMessage(),
                ],
            ])->save();

            throw $exception;
        }
    }

    public function factorToCurrentBase(Item $item, int $fromUomId): string
    {
        $toUomId = (int) $item->base_uom_id;
        if ($fromUomId === $toUomId) {
            return '1.000000';
        }

        $itemUnit = ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('uom_id', $fromUomId)
            ->where('is_active', true)
            ->first();
        if ($itemUnit instanceof ItemUnit) {
            return $this->math->normalize((string) $itemUnit->conversion_factor);
        }

        $factor = '1.000000';
        $currentUomId = $fromUomId;
        $revisions = ItemBaseUomRevision::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('status', ItemBaseUomRevisionStatus::Applied->value)
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();
        foreach ($revisions as $revision) {
            if ((int) $revision->old_base_uom_id !== $currentUomId) {
                continue;
            }
            $factor = $this->math->mul($factor, (string) $revision->conversion_factor);
            $currentUomId = (int) $revision->new_base_uom_id;
            if ($currentUomId === $toUomId) {
                return $factor;
            }
        }

        $result = $this->uomConversions->getConversionFactor($fromUomId, $toUomId, (int) $item->tenant_id);
        if ($result->isSuccess()) {
            return $this->math->normalize((string) $result->valueOrFail());
        }

        throw new \InvalidArgumentException('Document UOM cannot be converted to the current item base UOM.');
    }

    /**
     * @return array{quantity: string, unit_cost: string, factor: string}
     */
    public function convertOperationalBasis(Item $item, int $fromUomId, string $quantity, string $unitCost): array
    {
        $item = $this->ensureOperationalBaseUom($item, $fromUomId);
        $factor = $this->factorToCurrentBase($item, $fromUomId);

        return [
            'quantity' => $this->math->mul($quantity, $factor),
            'unit_cost' => $this->math->div($unitCost, $factor),
            'factor' => $factor,
        ];
    }

    private function ensureOperationalBaseUom(Item $item, int $fromUomId): Item
    {
        if ($item->base_uom_id !== null) {
            return $item;
        }

        return DB::transaction(function () use ($item, $fromUomId): Item {
            $locked = Item::query()->lockForUpdate()->findOrFail($item->getKey());
            if ($locked->base_uom_id === null) {
                $locked->base_uom_id = $fromUomId;
                $locked->save();
                $this->units->syncBaseUnit($locked);
            }

            return $locked->refresh();
        });
    }

    private function convertItemUnits(Item $item, int $newBaseUomId, string $factor): void
    {
        $units = ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->lockForUpdate()
            ->get();

        $baseUnit = $units->first(fn (ItemUnit $unit): bool => $unit->unit_role === ItemUnitRole::Base);
        foreach ($units as $unit) {
            if ($unit->unit_role === ItemUnitRole::Base) {
                continue;
            }
            $unit->conversion_factor = (int) $unit->uom_id === $newBaseUomId
                ? '1.000000'
                : $this->math->mul((string) $unit->conversion_factor, $factor);
            $unit->save();
        }

        if ($baseUnit instanceof ItemUnit) {
            $baseShouldBeDefault = (bool) $baseUnit->is_default
                || ! $this->hasValidNonBaseDefault($item);
            $baseUnit->fill([
                'uom_id' => $newBaseUomId,
                'conversion_factor' => '1.000000',
                'is_default' => $baseShouldBeDefault,
                'is_active' => true,
            ])->save();
            $this->normalizeDefaultUnits($item, $baseShouldBeDefault ? (int) $baseUnit->getKey() : null);

            return;
        }

        $makeDefault = ! $this->hasValidNonBaseDefault($item);
        ItemUnit::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'uom_id' => $newBaseUomId,
            'unit_role' => ItemUnitRole::Base,
            'conversion_factor' => '1.000000',
            'is_default' => $makeDefault,
            'is_active' => true,
        ]);
        $this->normalizeDefaultUnits($item);
    }

    private function hasValidNonBaseDefault(Item $item): bool
    {
        return ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('unit_role', '!=', ItemUnitRole::Base->value)
            ->where('is_default', true)
            ->where('is_active', true)
            ->exists();
    }

    private function normalizeDefaultUnits(Item $item, ?int $preferredDefaultId = null): void
    {
        if ($preferredDefaultId !== null) {
            ItemUnit::query()
                ->where('item_id', $item->getKey())
                ->whereKeyNot($preferredDefaultId)
                ->update(['is_default' => false]);

            return;
        }

        $keeper = ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->where('is_default', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (! $keeper instanceof ItemUnit) {
            ItemUnit::query()
                ->where('item_id', $item->getKey())
                ->update(['is_default' => false]);

            return;
        }

        ItemUnit::query()
            ->where('item_id', $item->getKey())
            ->whereKeyNot($keeper->getKey())
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validationSummary(array $validation): array
    {
        return [
            'is_valid' => $validation['is_valid'],
            'factor_source' => $validation['factor_source'],
            'affected_modules' => $validation['audit']['affected_modules'],
            'blockers' => $validation['blockers'],
            'warnings' => $validation['warnings'],
        ];
    }

    private function scope($query, Item $item)
    {
        $query->where('tenant_id', $item->tenant_id)->where('item_id', $item->getKey());
        if ($item->organization_unit_id !== null) {
            $query->where('organization_unit_id', $item->organization_unit_id);
        }

        return $query;
    }
}
