<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryBatchPriceRevision;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceLineValidator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $inventoryValidator,
    ) {}

    public function validate(VehicleServiceJob $job, VehicleServiceLineData $data): ?Item
    {
        if ($data->lineSourceType === VehicleServiceLineSourceType::ComboChild && $data->parentLineId === null) {
            throw new InvalidArgumentException('Combo child lines require a combo parent line.');
        }
        if ($data->lineSourceType !== VehicleServiceLineSourceType::ComboChild && $data->parentLineId !== null) {
            throw new InvalidArgumentException('Only combo child lines may reference a parent line.');
        }
        if ($data->isCustomerSupplied && ! in_array($data->lineSourceType, [
            VehicleServiceLineSourceType::InventoryItem,
            VehicleServiceLineSourceType::ExternalItem,
        ], true)) {
            throw new InvalidArgumentException('Customer supplied lines must be inventory or external item lines.');
        }

        $this->positive($data->quantity, 'Line quantity must be greater than zero.');
        foreach ([
            $data->unitCost,
            $data->unitPrice,
            $data->discountRate,
            $data->discountAmount,
            $data->taxRate,
            $data->taxAmount,
            $data->chargeRate,
            $data->chargeAmount,
        ] as $value) {
            $this->nonNegative($value, 'Line money and rate values cannot be negative.');
        }
        foreach ([
            $data->discountCalculationType,
            $data->taxCalculationType,
            $data->chargeCalculationType,
        ] as $type) {
            if ($type !== null && ! in_array($type, ['fixed', 'percentage'], true)) {
                throw new InvalidArgumentException('Line calculation type must be fixed or percentage.');
            }
        }

        if ($data->lineSourceType === VehicleServiceLineSourceType::ExternalItem) {
            $item = $data->itemId === null ? null : $this->item($job, $data->itemId);
            $this->validateReferences($job, $data, $item);

            return $item;
        }

        if ($data->itemId === null) {
            throw new InvalidArgumentException('Selected line source type requires an item.');
        }

        $item = $this->item($job, $data->itemId);
        $this->validateReferences($job, $data, $item);
        match ($data->lineSourceType) {
            VehicleServiceLineSourceType::InventoryItem => $this->assertInventoryItem($item),
            VehicleServiceLineSourceType::ServiceItem => $this->assertItemType($item, ItemType::Service),
            VehicleServiceLineSourceType::LabourItem => $this->assertItemType($item, ItemType::Labour),
            VehicleServiceLineSourceType::ComboParent => $this->assertCombo($item),
            VehicleServiceLineSourceType::ComboChild => $this->assertComboChild($job, $data->parentLineId, $item),
            VehicleServiceLineSourceType::ExternalItem => null,
        };
        $this->validateTracking($item, $data);

        return $item;
    }

    private function validateTracking(Item $item, VehicleServiceLineData $data): void
    {
        $tracking = $item->tracking_type instanceof TrackingType
            ? $item->tracking_type
            : TrackingType::from((string) $item->tracking_type);
        $batchTracked = in_array($tracking, [TrackingType::Batch, TrackingType::Lot], true);

        if ($batchTracked && $data->batchId === null) {
            throw ValidationException::withMessages(['batch_id' => ['Select a batch or lot for this inventory item.']]);
        }
        if (! $batchTracked && ($data->batchId !== null || $data->batchPriceRevisionId !== null)) {
            throw ValidationException::withMessages(['batch_id' => ['Batch references are only allowed for batch or lot tracked items.']]);
        }
        if (! $batchTracked) {
            return;
        }

        $batch = $this->inventoryValidator->batch($item, $data->batchId, $data->itemVariantId);
        if ($data->batchPriceRevisionId === null) {
            return;
        }

        $price = InventoryBatchPriceRevision::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('batch_id', $batch?->getKey())
            ->where('price_type', ItemPriceType::Service->value)
            ->whereNull('recorded_to')
            ->findOrFail($data->batchPriceRevisionId);
        if ((int) $price->uom_id !== (int) ($data->uomId ?? $item->base_uom_id)) {
            throw ValidationException::withMessages(['batch_price_revision_id' => ['The selected batch price does not match the line UOM.']]);
        }
    }

    private function item(VehicleServiceJob $job, int $itemId): Item
    {
        /** @var Item $item */
        $item = $this->scoped(Item::query(), $job)->findOrFail($itemId);
        if (! $item->is_active) {
            throw new InvalidArgumentException('Inactive items cannot be used on service jobs.');
        }

        return $item;
    }

    private function validateReferences(VehicleServiceJob $job, VehicleServiceLineData $data, ?Item $item): void
    {
        if ($data->itemVariantId !== null) {
            if ($item === null) {
                throw new InvalidArgumentException('Item variant requires an item.');
            }
            $variant = $this->scoped(ItemVariant::query(), $job)->findOrFail($data->itemVariantId);
            if ((int) $variant->item_id !== (int) $item->getKey() || ! $variant->is_active) {
                throw new InvalidArgumentException('Line item variant must be active and belong to the selected item.');
            }
        }

        if ($data->uomId !== null) {
            $uom = $this->scoped(UnitOfMeasureModel::query(), $job)->findOrFail($data->uomId);
            if (! $uom->is_active) {
                throw new InvalidArgumentException('Line unit of measure must be active.');
            }
            if ($item !== null) {
                $this->assertItemUomIsActive($item, $data->uomId);
            }
        }
    }

    private function assertItemUomIsActive(Item $item, int $uomId): void
    {
        if ($item->base_uom_id !== null && (int) $item->base_uom_id === $uomId) {
            return;
        }

        if (ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('uom_id', $uomId)
            ->where('is_active', true)
            ->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'uom_id' => ['Line unit of measure must be active for the selected item.'],
        ]);
    }

    private function assertInventoryItem(Item $item): void
    {
        if (! $item->is_stockable || in_array($item->item_type, [
            ItemType::Service,
            ItemType::Labour,
            ItemType::NonStock,
        ], true)) {
            throw new InvalidArgumentException('Inventory item lines require an active stockable item.');
        }
    }

    private function assertItemType(Item $item, ItemType $type): void
    {
        if ($item->item_type !== $type) {
            throw new InvalidArgumentException("Line item must be a {$type->value} item.");
        }
    }

    private function assertCombo(Item $item): void
    {
        if (! $item->is_combo && ! in_array($item->item_type, [ItemType::Combo, ItemType::Package], true)) {
            throw new InvalidArgumentException('Combo parent lines require a combo or package item.');
        }
    }

    private function assertComboChild(VehicleServiceJob $job, ?int $parentLineId, Item $item): void
    {
        $parent = $job->lines()->with('item')->findOrFail($parentLineId);
        if ($parent->line_source_type !== VehicleServiceLineSourceType::ComboParent || $parent->item === null) {
            throw new InvalidArgumentException('Combo child parent must be a combo parent line from the same service job.');
        }
        if (in_array($item->item_type, [ItemType::Combo, ItemType::Package, ItemType::Asset], true)) {
            throw new InvalidArgumentException('Combo child lines must use stock, consumable, non-stock, service, or labour items.');
        }
        if (! $parent->item->bundleLines()->where('child_item_id', $item->getKey())->exists()) {
            throw new InvalidArgumentException('Combo child item must belong to the selected combo parent.');
        }
    }

    private function positive(string $value, string $message): void
    {
        if ($this->math->compare($value, '0.000000') <= 0) {
            throw new InvalidArgumentException($message);
        }
    }

    private function nonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function scoped($query, VehicleServiceJob $job)
    {
        $query->where('tenant_id', $job->tenant_id);

        return $job->organization_unit_id === null
            ? $query->whereNull('organization_unit_id')
            : $query->where(function ($scope) use ($job): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $job->organization_unit_id);
            });
    }
}
