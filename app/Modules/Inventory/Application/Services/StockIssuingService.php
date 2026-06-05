<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockIssuingService
{
    public function __construct(
        private readonly InventoryServiceSupport $support,
        private readonly StockAvailabilityService $availability,
        private readonly InventoryTransactionService $transactions,
        private readonly InventoryValuationService $valuation,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{movements: array<int, array<string, mixed>>}
     */
    public function issue(array $payload): array
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
                $baseQuantity = $this->support->convertToBase($tenantId, (int) $line['item_id'], (int) $line['uom_id'], $baseUomId, (float) $line['quantity']);
                $available = $this->availability->check($this->support->stockCriteriaFromLine($tenantId, $line));
                if ($baseQuantity > $available['available_quantity'] + 0.0001) {
                    throw ValidationException::withMessages([
                        'stock' => ['Insufficient stock available for item '.$line['item_id'].'.'],
                    ]);
                }

                $valuation = $this->valuation->consumeCostLayersForIssue($this->support->stockCriteriaFromLine($tenantId, $line), $baseQuantity);
                $level = $this->support->adjustStockLevel($tenantId, $organizationUnitId, $line, $baseUomId, $baseQuantity, 'OUT');
                $movements[] = $this->transactions->record([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'direction' => 'OUT',
                    'movement_type' => $payload['movement_type'] ?? 'ISSUE',
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
                    'transaction_uom_id' => (int) $line['uom_id'],
                    'base_uom_id' => $baseUomId,
                    'quantity' => (float) $line['quantity'],
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $valuation['unit_cost'],
                    'total_cost' => $valuation['total_cost'],
                    'balance_quantity' => $level['balance_quantity'],
                    'performed_by' => $payload['performed_by'] ?? null,
                    'notes' => $line['notes'] ?? $payload['notes'] ?? null,
                ]);
            }

            return ['movements' => $movements];
        });
    }
}
