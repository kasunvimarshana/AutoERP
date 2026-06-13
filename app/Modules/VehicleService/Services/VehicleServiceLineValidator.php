<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceLineValidator
{
    public function __construct(private readonly DecimalMath $math) {}

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

        return $item;
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
        }
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
