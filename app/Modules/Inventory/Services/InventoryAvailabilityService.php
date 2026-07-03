<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAvailabilityResult;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryAvailabilityService
{
    private const QUANTITY_FIELDS = [
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_allocated',
        'quantity_available',
        'quantity_in_transit',
        'quantity_returned',
        'quantity_damaged',
        'quantity_quarantine',
        'quantity_expired',
        'quantity_scrapped',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockBalanceService $balances,
        private readonly InventoryValidationService $validator,
    ) {}

    public function availability(StockBalanceData $data): StockAvailabilityResult
    {
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        if ($data->batchId !== null) {
            $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        }

        $totals = $this->emptyQuantities();
        foreach ($this->matchingBalances($data) as $balance) {
            $this->balances->assertReconciled($balance);
            foreach (self::QUANTITY_FIELDS as $field) {
                $totals[$field] = $this->math->add($totals[$field], (string) ($balance->{$field} ?? '0.000000'));
            }
        }

        $quantityOnHand = $totals['quantity_on_hand'];
        $quantityInTransit = $totals['quantity_in_transit'];

        return new StockAvailabilityResult(
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityOnHand: $quantityOnHand,
            quantityReserved: $totals['quantity_reserved'],
            quantityAllocated: $totals['quantity_allocated'],
            quantityAvailable: $totals['quantity_available'],
            quantityInTransit: $quantityInTransit,
            quantityReturned: $totals['quantity_returned'],
            quantityDamaged: $totals['quantity_damaged'],
            quantityQuarantine: $totals['quantity_quarantine'],
            quantityExpired: $totals['quantity_expired'],
            quantityScrapped: $totals['quantity_scrapped'],
            quantityTotal: $this->math->add($quantityOnHand, $quantityInTransit),
            baseUomId: $item->base_uom_id,
            itemVariantId: $data->itemVariantId,
            warehouseLocationId: $data->warehouseLocationId,
            batchId: $data->batchId,
        );
    }

    /**
     * @return iterable<InventoryStockBalance>
     */
    private function matchingBalances(StockBalanceData $data): iterable
    {
        $query = InventoryStockBalance::query()
            ->where('tenant_id', $data->tenantId)
            ->where('item_id', $data->itemId)
            ->where('warehouse_id', $data->warehouseId);

        if ($data->organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where('organization_unit_id', $data->organizationUnitId);
        }

        foreach ([
            'item_variant_id' => $data->itemVariantId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
        ] as $column => $value) {
            if ($value !== null) {
                $query->where($column, $value);
            }
        }

        return $query->get();
    }

    /**
     * @return array<string, string>
     */
    private function emptyQuantities(): array
    {
        return array_fill_keys(self::QUANTITY_FIELDS, '0.000000');
    }
}
