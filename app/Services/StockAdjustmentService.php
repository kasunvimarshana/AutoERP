<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustmentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockAdjustment::where('tenant_id', $tenantId)
            ->with(['warehouse', 'createdBy']);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data, string $tenantId, string $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $tenantId, $userId) {
            $totalAmount = '0';

            $adjustment = StockAdjustment::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $data['warehouse_id'],
                'reference_no' => $data['reference_no'] ?? 'ADJ-'.strtoupper(Str::random(8)),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => '0', // updated after lines
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $lineAmount = bcmul((string) abs((float) $line['quantity']), $line['unit_cost'] ?? '0', 8);
                $totalAmount = bcadd($totalAmount, $lineAmount, 8);

                StockAdjustmentLine::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'quantity' => $line['quantity'], // can be negative
                    'unit_cost' => $line['unit_cost'] ?? '0',
                ]);

                // Apply inventory adjustment
                $this->inventoryService->adjust(
                    tenantId: $tenantId,
                    warehouseId: $data['warehouse_id'],
                    productId: $line['product_id'],
                    quantity: (string) $line['quantity'],
                    movementType: 'adjustment',
                    variantId: $line['product_variant_id'] ?? null,
                    notes: 'Stock adjustment: '.$adjustment->reference_no.' ('.$data['reason'].')',
                    referenceType: StockAdjustment::class,
                    referenceId: $adjustment->id
                );
            }

            $adjustment->update(['total_amount' => $totalAmount]);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: StockAdjustment::class,
                auditableId: $adjustment->id,
                newValues: ['reference_no' => $adjustment->reference_no, 'reason' => $data['reason']]
            );

            return $adjustment->fresh(['lines', 'warehouse']);
        });
    }
}
