<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function customer(int $tenantId, ?int $organizationUnitId, int $customerId): Customer
    {
        /** @var Customer $customer */
        $customer = $this->scoped(Customer::query(), $tenantId, $organizationUnitId)->findOrFail($customerId);

        return $customer;
    }

    public function vehicle(int $tenantId, ?int $organizationUnitId, int $vehicleId, int $customerId): Vehicle
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->scoped(Vehicle::query(), $tenantId, $organizationUnitId)->findOrFail($vehicleId);
        if (! in_array($vehicle->status, [VehicleStatus::Active, VehicleStatus::UnderService], true)) {
            throw new InvalidArgumentException('Inactive or unavailable vehicles cannot be used for service jobs.');
        }
        if ($vehicle->customer_id !== null && (int) $vehicle->customer_id !== $customerId) {
            throw new InvalidArgumentException('Vehicle does not belong to the selected customer.');
        }

        return $vehicle;
    }

    public function employee(int $tenantId, ?int $organizationUnitId, int $employeeId): HrEmployee
    {
        /** @var HrEmployee $employee */
        $employee = $this->scoped(HrEmployee::query(), $tenantId, $organizationUnitId)->findOrFail($employeeId);
        if ($employee->status !== EmployeeStatus::Active) {
            throw new InvalidArgumentException('Only active employees can be assigned to service jobs.');
        }

        return $employee;
    }

    public function item(int $tenantId, ?int $organizationUnitId, int $itemId): Item
    {
        /** @var Item $item */
        $item = $this->scoped(Item::query(), $tenantId, $organizationUnitId)->findOrFail($itemId);
        if (! $item->is_active) {
            throw new InvalidArgumentException('Inactive items cannot be used on service jobs.');
        }

        return $item;
    }

    public function validateLine(VehicleServiceJob $job, VehicleServiceLineData $data): ?Item
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
        foreach ([$data->discountCalculationType, $data->taxCalculationType, $data->chargeCalculationType] as $type) {
            if ($type !== null && ! in_array($type, ['fixed', 'percentage'], true)) {
                throw new InvalidArgumentException('Line calculation type must be fixed or percentage.');
            }
        }

        if ($data->lineSourceType === VehicleServiceLineSourceType::ExternalItem) {
            if ($data->itemId !== null) {
                $item = $this->item((int) $job->tenant_id, $job->organization_unit_id, $data->itemId);
                $this->validateLineReferences($job, $data, $item);

                return $item;
            }
            $this->validateLineReferences($job, $data, null);

            return null;
        }

        if ($data->itemId === null) {
            throw new InvalidArgumentException('Selected line source type requires an item.');
        }

        $item = $this->item((int) $job->tenant_id, $job->organization_unit_id, $data->itemId);
        $this->validateLineReferences($job, $data, $item);
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

    public function assertEmployeeAssignable(VehicleServiceJobLine $line): void
    {
        $line->loadMissing('item');
        $assignable = in_array($line->line_source_type, [
            VehicleServiceLineSourceType::ServiceItem,
            VehicleServiceLineSourceType::LabourItem,
        ], true);

        if ($line->line_source_type === VehicleServiceLineSourceType::ComboChild) {
            $assignable = in_array($line->item?->item_type, [ItemType::Service, ItemType::Labour], true);
        }

        if (! $assignable || ! $line->is_employee_assignable) {
            throw new InvalidArgumentException('Employees can only be assigned to service or labour lines.');
        }
    }

    public function assertInventoryIssueLine(VehicleServiceJobLine $line): void
    {
        $line->loadMissing('item');
        $eligibleType = $line->line_source_type === VehicleServiceLineSourceType::InventoryItem
            || ($line->line_source_type === VehicleServiceLineSourceType::ComboChild && (bool) $line->item?->is_stockable);
        if (! $eligibleType || ! $line->is_inventory_tracked || $line->is_customer_supplied || $line->is_external) {
            throw new InvalidArgumentException('Only inventory item lines can create stock issues.');
        }
    }

    public function assertMutable(VehicleServiceJob $job): void
    {
        if (! in_array($job->status->value, ['draft', 'inspected', 'in_progress'], true)) {
            throw new InvalidArgumentException('This service job can no longer be edited.');
        }
    }

    public function nonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function positive(string $value, string $message): void
    {
        if ($this->math->compare($value, '0.000000') <= 0) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertInventoryItem(Item $item): void
    {
        if (! $item->is_stockable || in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true)) {
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

    private function validateLineReferences(VehicleServiceJob $job, VehicleServiceLineData $data, ?Item $item): void
    {
        if ($data->itemVariantId !== null) {
            if ($item === null) {
                throw new InvalidArgumentException('Item variant requires an item.');
            }
            $variant = $this->scoped(ItemVariant::query(), (int) $job->tenant_id, $job->organization_unit_id)
                ->findOrFail($data->itemVariantId);
            if ((int) $variant->item_id !== (int) $item->getKey() || ! $variant->is_active) {
                throw new InvalidArgumentException('Line item variant must be active and belong to the selected item.');
            }
        }

        if ($data->uomId !== null) {
            $uom = $this->scoped(UnitOfMeasureModel::query(), (int) $job->tenant_id, $job->organization_unit_id)
                ->findOrFail($data->uomId);
            if (! $uom->is_active) {
                throw new InvalidArgumentException('Line unit of measure must be active.');
            }
        }
    }

    private function scoped($query, int $tenantId, ?int $organizationUnitId)
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            });
    }
}
