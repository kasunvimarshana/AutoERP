<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\PosReturn;
use App\Models\PosReturnLine;
use App\Models\PosTransaction;
use App\Models\PosTransactionLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosReturnService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PosReturn::where('tenant_id', $tenantId)
            ->with(['posTransaction', 'businessLocation', 'createdBy', 'lines']);

        if (isset($filters['pos_transaction_id'])) {
            $query->where('pos_transaction_id', $filters['pos_transaction_id']);
        }
        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data, string $tenantId, string $userId): PosReturn
    {
        return DB::transaction(function () use ($data, $tenantId, $userId) {
            $originalTx = PosTransaction::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($data['pos_transaction_id']);

            if ($originalTx->status === 'void') {
                throw new \RuntimeException('Cannot return a voided transaction.');
            }

            $totalRefund = '0';
            $lines = $data['lines'] ?? [];

            // Validate return quantities against original lines
            foreach ($lines as $line) {
                $originalLine = PosTransactionLine::where('pos_transaction_id', $originalTx->id)
                    ->findOrFail($line['pos_transaction_line_id']);

                // Calculate total already returned for this line
                $alreadyReturned = PosReturnLine::whereHas(
                    'posReturn',
                    fn ($q) => $q->where('pos_transaction_id', $originalTx->id)
                )
                    ->where('pos_transaction_line_id', $originalLine->id)
                    ->sum('quantity');

                $maxReturnable = bcsub($originalLine->quantity, (string) $alreadyReturned, 8);

                if (bccomp((string) $line['quantity'], $maxReturnable, 8) > 0) {
                    throw new \RuntimeException(
                        "Return quantity exceeds original quantity for product {$originalLine->product_id}."
                    );
                }

                $lineRefund = bcmul($line['quantity'], $originalLine->unit_price, 8);
                $totalRefund = bcadd($totalRefund, $lineRefund, 8);
            }

            $posReturn = PosReturn::create([
                'tenant_id' => $tenantId,
                'pos_transaction_id' => $originalTx->id,
                'business_location_id' => $data['business_location_id'] ?? $originalTx->business_location_id,
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? 'RET-'.strtoupper(Str::random(8)),
                'total_refund' => $totalRefund,
                'refund_method' => $data['refund_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $originalLine = PosTransactionLine::where('pos_transaction_id', $originalTx->id)
                    ->findOrFail($line['pos_transaction_line_id']);

                $lineRefund = bcmul($line['quantity'], $originalLine->unit_price, 8);

                PosReturnLine::create([
                    'pos_return_id' => $posReturn->id,
                    'pos_transaction_line_id' => $originalLine->id,
                    'product_id' => $originalLine->product_id,
                    'product_variant_id' => $originalLine->product_variant_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $originalLine->unit_price,
                    'refund_amount' => $lineRefund,
                    'restock' => $line['restock'] ?? true,
                ]);

                // Add back to inventory if restocking
                if ($line['restock'] ?? true) {
                    $this->inventoryService->adjust(
                        tenantId: $tenantId,
                        warehouseId: $data['warehouse_id'] ?? $posReturn->business_location_id,
                        productId: $originalLine->product_id,
                        quantity: (string) $line['quantity'],
                        movementType: 'pos_return',
                        variantId: $originalLine->product_variant_id,
                        notes: 'POS return: '.$posReturn->reference_no,
                        referenceType: PosReturn::class,
                        referenceId: $posReturn->id
                    );
                }
            }

            // Mark original transaction as refunded only if ALL line items are now fully returned
            $allFullyReturned = true;
            foreach ($originalTx->lines as $txLine) {
                $totalReturned = PosReturnLine::whereHas(
                    'posReturn',
                    fn ($q) => $q->where('pos_transaction_id', $originalTx->id)
                )
                    ->where('pos_transaction_line_id', $txLine->id)
                    ->sum('quantity');

                if (bccomp((string) $totalReturned, $txLine->quantity, 8) < 0) {
                    $allFullyReturned = false;
                    break;
                }
            }

            if ($allFullyReturned) {
                $originalTx->update(['status' => 'refunded']);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: PosReturn::class,
                auditableId: $posReturn->id,
                newValues: ['reference_no' => $posReturn->reference_no, 'total_refund' => $totalRefund]
            );

            return $posReturn->fresh(['lines', 'posTransaction']);
        });
    }
}
