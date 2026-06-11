<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryStockStateChange;

final class InventoryStockStateService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function record(
        InventoryStockBalance|InventoryMovement $record,
        ?InventoryStockState $fromState,
        ?InventoryStockState $toState,
        string $quantity,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $sourceLineType = null,
        ?int $sourceLineId = null,
        ?string $description = null,
        ?int $userId = null,
    ): InventoryStockStateChange {
        $quantity = $this->math->normalize($quantity);

        return InventoryStockStateChange::query()->create([
            'tenant_id' => $record->tenant_id,
            'organization_unit_id' => $record->organization_unit_id,
            'stock_balance_id' => $record instanceof InventoryStockBalance ? $record->getKey() : null,
            'item_id' => $record->item_id,
            'item_variant_id' => $record->item_variant_id,
            'warehouse_id' => $record->warehouse_id,
            'warehouse_location_id' => $record->warehouse_location_id,
            'batch_id' => $record->batch_id,
            'serial_number_id' => $record instanceof InventoryMovement ? $record->serial_number_id : null,
            'from_state' => $fromState,
            'to_state' => $toState,
            'quantity' => $quantity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_line_type' => $sourceLineType,
            'source_line_id' => $sourceLineId,
            'description' => $description,
            'created_by' => $userId,
            'occurred_at' => now(),
        ]);
    }
}
