<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Services;

use Illuminate\Support\Facades\DB;

final class InventoryTransactionService
{
    /** @param array<string, mixed> $context */
    public function receive(
        int $itemId,
        float $quantity,
        float $unitCost,
        int $warehouseId,
        string $referenceType,
        int $referenceId,
        array $context = [],
    ): int {
        return $this->move('IN', 'PURCHASE_RECEIPT', $itemId, $quantity, $unitCost, $warehouseId, $referenceType, $referenceId, $context);
    }

    /** @param array<string, mixed> $context */
    public function issue(
        int $itemId,
        float $quantity,
        float $unitCost,
        int $warehouseId,
        string $reason,
        int $referenceId,
        array $context = [],
    ): int {
        return $this->move('OUT', $reason, $itemId, $quantity, $unitCost, $warehouseId, (string) ($context['reference_type'] ?? $reason), $referenceId, $context);
    }

    /** @param array<string, mixed> $context */
    public function reserve(int $itemId, float $quantity, int $warehouseId, string $referenceType, int $referenceId, array $context = []): void
    {
        $level = $this->stockLevel($itemId, $warehouseId, $context);
        if ($level === null || (float) $level->quantity_on_hand - (float) $level->quantity_reserved < $quantity) {
            throw new \RuntimeException('Insufficient available stock to reserve.');
        }

        DB::table('stock_levels')->where('id', (int) $level->id)->update([
            'quantity_reserved' => (float) $level->quantity_reserved + $quantity,
            'row_version' => (int) $level->row_version + 1,
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $context */
    public function release(int $itemId, float $quantity, int $warehouseId, string $referenceType, int $referenceId, array $context = []): void
    {
        $level = $this->stockLevel($itemId, $warehouseId, $context);
        if ($level === null) {
            return;
        }

        DB::table('stock_levels')->where('id', (int) $level->id)->update([
            'quantity_reserved' => max(0, (float) $level->quantity_reserved - $quantity),
            'row_version' => (int) $level->row_version + 1,
            'updated_at' => now(),
        ]);
    }

    public function reverseMovement(int $movementId): int
    {
        $movement = DB::table('stock_movements')->lockForUpdate()->find($movementId);
        if ($movement === null) {
            throw new \RuntimeException('Stock movement not found.');
        }

        return $this->move(
            (float) $movement->quantity_in > 0 ? 'OUT' : 'IN',
            'REVERSAL',
            (int) $movement->item_id,
            (float) $movement->quantity,
            (float) $movement->unit_cost,
            (int) $movement->warehouse_id,
            (string) $movement->reference_type,
            (int) $movement->reference_id,
            (array) $movement,
        );
    }

    /** @param array<string, mixed> $context */
    private function move(string $direction, string $txnType, int $itemId, float $quantity, float $unitCost, int $warehouseId, string $referenceType, int $referenceId, array $context): int
    {
        if ($quantity <= 0) {
            throw new \RuntimeException('Inventory quantity must be greater than zero.');
        }

        $level = $this->stockLevel($itemId, $warehouseId, $context);
        $current = $level === null ? 0.0 : (float) $level->quantity_on_hand;
        $newQty = $direction === 'IN' ? $current + $quantity : $current - $quantity;
        if ($newQty < 0) {
            throw new \RuntimeException('Stock movement would create negative inventory.');
        }

        $movementUnitCost = $direction === 'OUT'
            ? $this->consumeCostLayers($itemId, $quantity, $warehouseId, $context, $level)
            : $unitCost;
        $levelUnitCost = $direction === 'IN'
            ? $this->weightedAverageCost($current, $level === null ? 0.0 : (float) $level->unit_cost, $quantity, $unitCost)
            : ($newQty > 0 ? ($level === null ? $movementUnitCost : (float) $level->unit_cost) : $movementUnitCost);

        if ($level === null) {
            DB::table('stock_levels')->insert([
                'tenant_id' => (int) $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'] ?? null,
                'item_id' => $itemId,
                'variant_id' => $context['variant_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'location_id' => $context['location_id'] ?? null,
                'batch_id' => $context['batch_id'] ?? null,
                'serial_id' => $context['serial_id'] ?? null,
                'uom_id' => (int) $context['uom_id'],
                'quantity_on_hand' => $newQty,
                'quantity_reserved' => 0,
                'unit_cost' => $levelUnitCost,
                'last_movement_at' => now(),
                'condition' => (string) ($context['condition'] ?? 'good'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('stock_levels')->where('id', (int) $level->id)->update([
                'quantity_on_hand' => $newQty,
                'unit_cost' => $levelUnitCost,
                'last_movement_at' => now(),
                'row_version' => (int) $level->row_version + 1,
                'updated_at' => now(),
            ]);
        }

        $movementId = (int) DB::table('stock_movements')->insertGetId([
            'tenant_id' => (int) $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'] ?? null,
            'direction' => $direction,
            'item_id' => $itemId,
            'variant_id' => $context['variant_id'] ?? null,
            'batch_id' => $context['batch_id'] ?? null,
            'serial_id' => $context['serial_id'] ?? null,
            'location_id' => $context['location_id'] ?? null,
            'warehouse_id' => $warehouseId,
            'txn_type' => $txnType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'uom_id' => (int) $context['uom_id'],
            'quantity' => $quantity,
            'quantity_in' => $direction === 'IN' ? $quantity : 0,
            'quantity_out' => $direction === 'OUT' ? $quantity : 0,
            'unit_cost' => $movementUnitCost,
            'total_cost' => round($quantity * $movementUnitCost, 4),
            'balance_quantity' => $newQty,
            'balance_value' => round($newQty * $levelUnitCost, 4),
            'performed_by' => $context['created_by'] ?? null,
            'performed_at' => now(),
            'notes' => $context['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($direction === 'IN') {
            DB::table('inventory_cost_layers')->insert([
                'tenant_id' => (int) $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'] ?? null,
                'item_id' => $itemId,
                'variant_id' => $context['variant_id'] ?? null,
                'batch_id' => $context['batch_id'] ?? null,
                'serial_id' => $context['serial_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'location_id' => $context['location_id'] ?? null,
                'valuation_method' => DB::table('items')->where('id', $itemId)->value('valuation_method'),
                'layer_date' => now()->toDateString(),
                'quantity_in' => $quantity,
                'quantity_remaining' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $movementId;
    }

    /** @param array<string, mixed> $context */
    private function consumeCostLayers(int $itemId, float $quantity, int $warehouseId, array $context, ?object $level): float
    {
        $method = strtoupper((string) (DB::table('items')->where('id', $itemId)->value('valuation_method') ?? 'FIFO'));
        $isAverage = str_contains($method, 'AVERAGE') || str_contains($method, 'AVG') || str_contains($method, 'WEIGHTED');
        $costFromLevel = $level === null ? 0.0 : (float) $level->unit_cost;
        $remaining = $quantity;
        $totalCost = 0.0;

        $layers = DB::table('inventory_cost_layers')
            ->where('tenant_id', (int) $context['tenant_id'])
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->when(empty($context['variant_id']), static fn ($query) => $query->whereNull('variant_id'), static fn ($query) => $query->where('variant_id', (int) $context['variant_id']))
            ->when(empty($context['location_id']), static fn ($query) => $query->whereNull('location_id'), static fn ($query) => $query->where('location_id', (int) $context['location_id']))
            ->when(empty($context['batch_id']), static fn ($query) => $query->whereNull('batch_id'), static fn ($query) => $query->where('batch_id', (int) $context['batch_id']))
            ->when(empty($context['serial_id']), static fn ($query) => $query->whereNull('serial_id'), static fn ($query) => $query->where('serial_id', (int) $context['serial_id']))
            ->where('is_closed', false)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('layer_date', $method === 'LIFO' ? 'desc' : 'asc')
            ->orderBy('id', $method === 'LIFO' ? 'desc' : 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consumeQty = min($remaining, (float) $layer->quantity_remaining);
            $remaining = round($remaining - $consumeQty, 4);
            $newRemaining = round((float) $layer->quantity_remaining - $consumeQty, 4);
            $totalCost += $consumeQty * ($isAverage && $costFromLevel > 0 ? $costFromLevel : (float) $layer->unit_cost);

            DB::table('inventory_cost_layers')->where('id', (int) $layer->id)->update([
                'quantity_remaining' => $newRemaining,
                'is_closed' => $newRemaining <= 0,
                'row_version' => (int) $layer->row_version + 1,
                'updated_at' => now(),
            ]);
        }

        if ($remaining > 0.0001) {
            throw new \RuntimeException('Insufficient inventory cost layers for stock issue.');
        }

        return round($totalCost / $quantity, 4);
    }

    private function weightedAverageCost(float $currentQty, float $currentUnitCost, float $incomingQty, float $incomingUnitCost): float
    {
        $newQty = $currentQty + $incomingQty;
        if ($newQty <= 0) {
            return round($incomingUnitCost, 4);
        }

        return round((($currentQty * $currentUnitCost) + ($incomingQty * $incomingUnitCost)) / $newQty, 4);
    }

    /** @param array<string, mixed> $context */
    private function stockLevel(int $itemId, int $warehouseId, array $context): ?object
    {
        return DB::table('stock_levels')
            ->where('tenant_id', (int) $context['tenant_id'])
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->when(empty($context['variant_id']), static fn ($query) => $query->whereNull('variant_id'), static fn ($query) => $query->where('variant_id', (int) $context['variant_id']))
            ->when(empty($context['location_id']), static fn ($query) => $query->whereNull('location_id'), static fn ($query) => $query->where('location_id', (int) $context['location_id']))
            ->when(empty($context['batch_id']), static fn ($query) => $query->whereNull('batch_id'), static fn ($query) => $query->where('batch_id', (int) $context['batch_id']))
            ->when(empty($context['serial_id']), static fn ($query) => $query->whereNull('serial_id'), static fn ($query) => $query->where('serial_id', (int) $context['serial_id']))
            ->where('condition', (string) ($context['condition'] ?? 'good'))
            ->lockForUpdate()
            ->first();
    }
}
