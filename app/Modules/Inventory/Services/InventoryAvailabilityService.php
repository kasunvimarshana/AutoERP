<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAvailabilityResult;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryTransferLine;

final class InventoryAvailabilityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockBalanceService $balances,
    ) {}

    public function availability(StockBalanceData $data): StockAvailabilityResult
    {
        $balance = $this->balances->getOrCreate($data);
        $inTransit = $this->inTransitQuantity($data);
        $quantityTotal = $this->math->add((string) $balance->quantity_on_hand, $inTransit);

        return new StockAvailabilityResult(
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityOnHand: (string) $balance->quantity_on_hand,
            quantityReserved: (string) $balance->quantity_reserved,
            quantityAllocated: (string) $balance->quantity_allocated,
            quantityAvailable: (string) $balance->quantity_available,
            quantityInTransit: $inTransit,
            quantityReturned: (string) ($balance->quantity_returned ?? '0.000000'),
            quantityDamaged: (string) ($balance->quantity_damaged ?? '0.000000'),
            quantityQuarantine: (string) ($balance->quantity_quarantine ?? '0.000000'),
            quantityExpired: (string) ($balance->quantity_expired ?? '0.000000'),
            quantityScrapped: (string) ($balance->quantity_scrapped ?? '0.000000'),
            quantityTotal: $quantityTotal,
            itemVariantId: $data->itemVariantId,
            warehouseLocationId: $data->warehouseLocationId,
            batchId: $data->batchId,
        );
    }

    private function inTransitQuantity(StockBalanceData $data): string
    {
        $query = InventoryTransferLine::query()
            ->join('inventory_transfers', 'inventory_transfers.id', '=', 'inventory_transfer_lines.inventory_transfer_id')
            ->where('inventory_transfer_lines.tenant_id', $data->tenantId)
            ->where('inventory_transfer_lines.item_id', $data->itemId)
            ->where('inventory_transfers.to_warehouse_id', $data->warehouseId)
            ->whereIn('inventory_transfers.status', [
                TransferStatus::Dispatched->value,
                TransferStatus::InTransit->value,
            ]);

        foreach ([
            'inventory_transfer_lines.organization_unit_id' => $data->organizationUnitId,
            'inventory_transfer_lines.item_variant_id' => $data->itemVariantId,
            'inventory_transfer_lines.batch_id' => $data->batchId,
        ] as $column => $value) {
            $query->where($column, $value);
        }

        if ($data->warehouseLocationId !== null) {
            $query->where('inventory_transfers.to_warehouse_location_id', $data->warehouseLocationId);
        }

        $lines = $query
            ->select([
                'inventory_transfer_lines.quantity',
                'inventory_transfer_lines.received_quantity',
                'inventory_transfer_lines.cancelled_quantity',
            ])
            ->get();

        $total = '0.000000';
        foreach ($lines as $line) {
            $remaining = $this->math->sub(
                $this->math->sub((string) $line->quantity, (string) $line->received_quantity),
                (string) $line->cancelled_quantity,
            );
            if (! $this->math->isNegative($remaining)) {
                $total = $this->math->add($total, $remaining);
            }
        }

        return $total;
    }
}
