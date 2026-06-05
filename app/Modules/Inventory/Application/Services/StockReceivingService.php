<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockReceivingService
{
    public function __construct(
        private readonly InventoryServiceSupport $support,
        private readonly InventoryTransactionService $transactions,
        private readonly InventoryValuationService $valuation,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{movements: array<int, array<string, mixed>>}
     */
    public function receive(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $tenantId = $this->support->tenantId($payload);
            $organizationUnitId = $this->support->organizationUnitId($payload);
            $lines = $this->support->normalizeLines($payload);
            $this->support->validateReferences($tenantId, $lines);
            $baseUoms = $this->support->itemBaseUomMap($tenantId, $lines);
            $movements = [];

            foreach ($lines as $line) {
                $baseUomId = $baseUoms[(int) $line['item_id']];
                $baseQuantity = $this->support->convertToBase(
                    $tenantId,
                    (int) $line['item_id'],
                    (int) $line['uom_id'],
                    $baseUomId,
                    (float) $line['quantity'],
                );
                $level = $this->support->adjustStockLevel(
                    $tenantId,
                    $organizationUnitId,
                    $line,
                    $baseUomId,
                    $baseQuantity,
                    'IN',
                    0,
                    isset($line['unit_cost']) ? (float) $line['unit_cost'] : null,
                );
                $movement = [
                    ...$this->movementBase($payload, $line, $tenantId, $organizationUnitId),
                    'direction' => 'IN',
                    'movement_type' => $payload['movement_type'] ?? 'RECEIPT',
                    'transaction_uom_id' => (int) $line['uom_id'],
                    'base_uom_id' => $baseUomId,
                    'quantity' => (float) $line['quantity'],
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => isset($line['unit_cost']) ? (float) $line['unit_cost'] : null,
                    'balance_quantity' => $level['balance_quantity'],
                    'notes' => $line['notes'] ?? $payload['notes'] ?? null,
                ];
                $recorded = $this->transactions->record($movement);
                $this->valuation->createCostLayerForReceipt($movement, $line);
                $movements[] = $recorded;
            }

            return ['movements' => $movements];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function movementBase(array $payload, array $line, int $tenantId, ?int $organizationUnitId): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => (int) $line['item_id'],
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'warehouse_id' => $line['warehouse_id'] ?? null,
            'location_id' => $line['location_id'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'source_module' => $payload['source_module'] ?? null,
            'source_reference' => $payload['source_reference'] ?? null,
            'source_line_id' => $line['source_line_id'] ?? null,
            'performed_by' => $payload['performed_by'] ?? null,
        ];
    }
}
