<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockAdjustmentService
{
    public function __construct(
        private readonly InventoryServiceSupport $support,
        private readonly StockReceivingService $receiving,
        private readonly StockIssuingService $issuing,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function adjust(array $payload): array
    {
        $type = (string) ($payload['type'] ?? '');
        if (! in_array($type, ['adjustment_in', 'adjustment_out'], true)) {
            throw ValidationException::withMessages([
                'type' => ['Adjustment type must be adjustment_in or adjustment_out.'],
            ]);
        }

        if (trim((string) ($payload['reason'] ?? $payload['notes'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'reason' => ['A stock adjustment reason is required.'],
            ]);
        }
        if (! isset($payload['warehouse_id']) || (int) $payload['warehouse_id'] <= 0) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['Warehouse ID is required.'],
            ]);
        }

        return DB::transaction(function () use ($payload, $type): array {
            $tenantId = $this->support->tenantId($payload);
            $this->support->validateWarehouseScope(
                $tenantId,
                [(int) ($payload['warehouse_id'] ?? 0)],
                [isset($payload['location_id']) ? (int) $payload['location_id'] : null],
            );
            $organizationUnitId = $this->support->organizationUnitId($payload);
            $lines = $this->support->normalizeLines($payload);
            $this->support->validateReferences($tenantId, $lines);
            $baseUoms = $this->support->itemBaseUomMap($tenantId, $lines);
            $adjustmentId = DB::table('stock_adjustments')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'reference_number' => $payload['reference_number'] ?? 'ADJ-'.now()->format('YmdHisv'),
                'warehouse_id' => (int) $payload['warehouse_id'],
                'location_id' => $payload['location_id'] ?? null,
                'type' => $type,
                'status' => 'COMPLETED',
                'counted_by' => $payload['performed_by'] ?? null,
                'counted_at' => now(),
                'approved_by' => $payload['approved_by'] ?? null,
                'approved_at' => now(),
                'reason' => $payload['reason'] ?? $payload['notes'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $movementResult = $type === 'adjustment_in'
                ? $this->receiving->receive([...$payload, 'source_type' => 'manual_adjustment', 'source_id' => $adjustmentId, 'movement_type' => 'ADJUSTMENT_IN'])
                : $this->issuing->issue([...$payload, 'source_type' => 'manual_adjustment', 'source_id' => $adjustmentId, 'movement_type' => 'ADJUSTMENT_OUT']);
            $this->insertAdjustmentLines($adjustmentId, $payload, $type, $lines, $baseUoms, $movementResult['movements']);

            return ['adjustment_id' => $adjustmentId, ...$movementResult];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, int>  $baseUoms
     * @param  array<int, array<string, mixed>>  $movements
     */
    private function insertAdjustmentLines(int $adjustmentId, array $payload, string $type, array $lines, array $baseUoms, array $movements): void
    {
        $tenantId = $this->support->tenantId($payload);
        $organizationUnitId = $this->support->organizationUnitId($payload);
        $isIncrease = $type === 'adjustment_in';

        foreach ($lines as $index => $line) {
            $movement = $movements[$index] ?? [];
            $baseAdjustmentQuantity = (float) ($movement['base_quantity'] ?? $line['quantity']);
            $resultingBaseQuantity = (float) ($movement['balance_quantity'] ?? 0);
            $currentBaseQuantity = $isIncrease
                ? $resultingBaseQuantity - $baseAdjustmentQuantity
                : $resultingBaseQuantity + $baseAdjustmentQuantity;
            $ratio = $baseAdjustmentQuantity > 0
                ? ((float) $line['quantity'] / $baseAdjustmentQuantity)
                : 1;

            DB::table('stock_adjustment_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'stock_adjustment_id' => $adjustmentId,
                'item_id' => (int) $line['item_id'],
                'variant_id' => $line['variant_id'] ?? null,
                'batch_id' => $line['batch_id'] ?? null,
                'serial_id' => $line['serial_id'] ?? null,
                'location_id' => $line['location_id'] ?? null,
                'warehouse_id' => (int) $line['warehouse_id'],
                'transaction_uom_id' => (int) $line['uom_id'],
                'base_uom_id' => $baseUoms[(int) $line['item_id']],
                'direction' => $isIncrease ? 'INCREASE' : 'DECREASE',
                'current_quantity' => $currentBaseQuantity * $ratio,
                'base_current_quantity' => $currentBaseQuantity,
                'adjustment_quantity' => (float) $line['quantity'],
                'base_adjustment_quantity' => $baseAdjustmentQuantity,
                'resulting_quantity' => $resultingBaseQuantity * $ratio,
                'base_resulting_quantity' => $resultingBaseQuantity,
                'unit_cost' => $movement['unit_cost'] ?? $line['unit_cost'] ?? null,
                'reason_code' => $payload['reason_code'] ?? null,
                'notes' => $line['notes'] ?? $payload['notes'] ?? null,
                'variance_value' => isset($movement['unit_cost']) ? ((float) $movement['unit_cost'] * $baseAdjustmentQuantity) : null,
                'adjustment_movement_id' => $movement['movement_id'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
