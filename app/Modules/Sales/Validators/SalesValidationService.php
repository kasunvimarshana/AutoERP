<?php

declare(strict_types=1);

namespace Modules\Sales\Validators;

use InvalidArgumentException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Models\ItemVariant;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrderLine;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\UOM\Services\UomConversionService;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class SalesValidationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly UomConversionService $conversions,
    ) {}

    public function assertPositive(string $value, string $message = 'Sales quantity must be greater than zero.'): void
    {
        if ($this->math->compare($value, '0.000000') <= 0) {
            throw new InvalidArgumentException($message);
        }
    }

    public function assertNonNegative(string $value, string $message = 'Sales amount cannot be negative.'): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    public function customer(int $tenantId, ?int $organizationUnitId, int $customerId): Customer
    {
        $customer = Customer::query()->with(['creditProfile', 'defaultCurrency'])->findOrFail($customerId);
        $this->assertTenantOrg((int) $customer->tenant_id, $customer->organization_unit_id, $tenantId, $organizationUnitId);
        if ($customer->status !== CustomerStatus::Active) {
            throw new InvalidArgumentException('Sales customer must be active.');
        }

        return $customer;
    }

    public function item(int $tenantId, ?int $organizationUnitId, int $itemId): Item
    {
        $item = Item::query()->findOrFail($itemId);
        $this->assertTenantOrg((int) $item->tenant_id, $item->organization_unit_id, $tenantId, $organizationUnitId);
        if (! (bool) $item->is_active) {
            throw new InvalidArgumentException('Sales item must be active.');
        }

        return $item;
    }

    public function itemVariant(int $tenantId, ?int $organizationUnitId, int $itemId, int $variantId): ItemVariant
    {
        $variant = ItemVariant::query()->findOrFail($variantId);
        $this->assertTenantOrg((int) $variant->tenant_id, $variant->organization_unit_id, $tenantId, $organizationUnitId);
        if ((int) $variant->item_id !== $itemId || ! (bool) $variant->is_active) {
            throw new InvalidArgumentException('Sales item variant must be active and belong to the selected item.');
        }

        return $variant;
    }

    public function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->findOrFail($warehouseId);
        $this->assertTenantOrg((int) $warehouse->tenant_id, $warehouse->organization_unit_id ?? null, $tenantId, $organizationUnitId);
        if (! (bool) $warehouse->is_active) {
            throw new InvalidArgumentException('Sales warehouse must be active.');
        }

        return $warehouse;
    }

    public function warehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, int $locationId): WarehouseLocationModel
    {
        $location = WarehouseLocationModel::query()->findOrFail($locationId);
        $this->assertTenantOrg((int) $location->tenant_id, $location->organization_unit_id ?? null, $tenantId, $organizationUnitId);
        if ((int) $location->warehouse_id !== $warehouseId || ! (bool) $location->is_active) {
            throw new InvalidArgumentException('Sales warehouse location must be active and belong to the selected warehouse.');
        }

        return $location;
    }

    public function currency(int $tenantId, ?int $organizationUnitId, int $currencyId): CurrencyModel
    {
        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $this->assertTenantOrg((int) $currency->tenant_id, $currency->organization_unit_id ?? null, $tenantId, $organizationUnitId);
        if (! (bool) $currency->is_active) {
            throw new InvalidArgumentException('Sales currency must be active.');
        }

        return $currency;
    }

    /**
     * @return array{ordered_uom_id: int, base_uom_id: int, factor: string, base_quantity: string}
     */
    public function resolveUom(int $tenantId, ?int $organizationUnitId, Item $item, int $uomId, string $quantity): array
    {
        $uom = UnitOfMeasureModel::query()->findOrFail($uomId);
        $this->assertTenantOrg((int) $uom->tenant_id, $uom->organization_unit_id, $tenantId, $organizationUnitId);
        if (! (bool) $uom->is_active) {
            throw new InvalidArgumentException('Sales UOM must be active.');
        }

        $baseUomId = (int) ($item->base_uom_id ?: $uomId);
        $factor = '1.000000';
        if ($uomId !== $baseUomId) {
            $itemUnit = ItemUnit::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('uom_id', $uomId)
                ->where('is_active', true)
                ->first();
            if ($itemUnit instanceof ItemUnit) {
                $factor = $this->math->normalize((string) $itemUnit->conversion_factor);
            } else {
                $result = $this->conversions->getConversionFactor($uomId, $baseUomId, $tenantId);
                if ($result->isFailure()) {
                    throw new InvalidArgumentException('Sales UOM conversion is required but no conversion exists.');
                }
                $factor = $this->math->normalize((string) $result->valueOrFail());
            }
        }

        return [
            'ordered_uom_id' => $uomId,
            'base_uom_id' => $baseUomId,
            'factor' => $factor,
            'base_quantity' => $this->math->mul($quantity, $factor),
        ];
    }

    public function assertTenantOrg(?int $actualTenantId, ?int $actualOrgId, int $tenantId, ?int $organizationUnitId): void
    {
        if ($actualTenantId !== null && $actualTenantId !== $tenantId) {
            throw new InvalidArgumentException('Sales reference belongs to a different tenant.');
        }
        if ($organizationUnitId !== null && $actualOrgId !== null && $actualOrgId !== $organizationUnitId) {
            throw new InvalidArgumentException('Sales reference belongs to a different organization unit.');
        }
    }

    public function assertDeliveryWithinOrder(SalesOrderLine $line, string $quantity): void
    {
        if ($this->math->compare($quantity, (string) $line->remaining_deliverable_quantity) > 0) {
            throw new InvalidArgumentException('Delivered quantity cannot exceed sales order remaining deliverable quantity.');
        }
    }

    public function assertReturnWithinDelivery(SalesDeliveryLine $line, string $quantity): void
    {
        $remaining = $this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity);
        if ($this->math->compare($quantity, $remaining) > 0) {
            throw new InvalidArgumentException('Returned quantity cannot exceed delivery remaining quantity.');
        }
    }
}
