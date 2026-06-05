<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;

final class InventoryTransactionService
{
    public function __construct(private readonly CurrentUserContextAccessorInterface $currentUser) {}

    /**
     * @param  array<string, mixed>  $movement
     * @return array<string, mixed>
     */
    public function record(array $movement): array
    {
        $this->preventDuplicateSourceMovement($movement);

        $direction = (string) $movement['direction'];
        if (! in_array($direction, ['IN', 'OUT'], true)) {
            throw ValidationException::withMessages([
                'direction' => ['Inventory movement direction must be IN or OUT.'],
            ]);
        }

        $quantity = (float) $movement['quantity'];
        $baseQuantity = (float) $movement['base_quantity'];
        $unitCost = isset($movement['unit_cost']) ? (float) $movement['unit_cost'] : null;
        $totalCost = isset($movement['total_cost'])
            ? (float) $movement['total_cost']
            : ($unitCost === null ? 0 : $unitCost * $baseQuantity);

        $id = DB::table('stock_movements')->insertGetId([
            'tenant_id' => (int) $movement['tenant_id'],
            'organization_unit_id' => $movement['organization_unit_id'] ?? null,
            'direction' => $direction,
            'movement_type' => (string) $movement['movement_type'],
            'item_id' => (int) $movement['item_id'],
            'variant_id' => $movement['variant_id'] ?? null,
            'batch_id' => $movement['batch_id'] ?? null,
            'serial_id' => $movement['serial_id'] ?? null,
            'warehouse_id' => $movement['warehouse_id'] ?? null,
            'location_id' => $movement['location_id'] ?? null,
            'source_type' => $movement['source_type'] ?? null,
            'source_id' => $movement['source_id'] ?? null,
            'source_module' => $movement['source_module'] ?? null,
            'source_reference' => $movement['source_reference'] ?? null,
            'source_line_id' => $movement['source_line_id'] ?? null,
            'transaction_uom_id' => (int) $movement['transaction_uom_id'],
            'base_uom_id' => (int) $movement['base_uom_id'],
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'quantity_in' => $direction === 'IN' ? $quantity : 0,
            'quantity_out' => $direction === 'OUT' ? $quantity : 0,
            'base_quantity_in' => $direction === 'IN' ? $baseQuantity : 0,
            'base_quantity_out' => $direction === 'OUT' ? $baseQuantity : 0,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_quantity' => $movement['balance_quantity'] ?? 0,
            'balance_value' => $movement['balance_value'] ?? 0,
            'status' => 'POSTED',
            'performed_by' => $movement['performed_by'] ?? $this->currentUser->currentUserId(),
            'performed_at' => $movement['performed_at'] ?? now(),
            'notes' => $movement['notes'] ?? null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'movement_id' => $id,
            'movement_type' => (string) $movement['movement_type'],
            'direction' => $direction,
            'transaction_uom_id' => (int) $movement['transaction_uom_id'],
            'base_uom_id' => (int) $movement['base_uom_id'],
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_quantity' => (float) ($movement['balance_quantity'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $movement
     */
    private function preventDuplicateSourceMovement(array $movement): void
    {
        if (
            ! isset($movement['source_type'], $movement['source_id'], $movement['source_line_id'])
            || $movement['source_type'] === null
            || $movement['source_id'] === null
            || $movement['source_line_id'] === null
        ) {
            return;
        }

        $exists = DB::table('stock_movements')
            ->where('tenant_id', (int) $movement['tenant_id'])
            ->where('source_type', (string) $movement['source_type'])
            ->where('source_id', (int) $movement['source_id'])
            ->where('source_line_id', (int) $movement['source_line_id'])
            ->where('movement_type', (string) $movement['movement_type'])
            ->where('status', 'POSTED')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'source' => ['Duplicate source transaction posting detected.'],
            ]);
        }
    }
}
