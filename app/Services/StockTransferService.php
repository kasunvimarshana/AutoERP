<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Manages inter-warehouse stock transfers.
 *
 * Lifecycle:
 *   1. `create()` — creates a draft transfer with line items.
 *   2. `dispatch()` — moves status to in_transit; records transfer_out on source warehouse.
 *   3. `receive()` — moves status to received; records receipt on destination warehouse.
 *   4. `cancel()` — cancels a draft or in_transit transfer (reverses inventory if in_transit).
 */
class StockTransferService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AuditService $auditService,
        private readonly ReferenceNumberService $referenceNumberService
    ) {}

    /**
     * Paginate stock transfers for a tenant.
     */
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockTransfer::where('tenant_id', $tenantId)
            ->with(['fromWarehouse', 'toWarehouse', 'lines.product', 'lines.variant']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $filters['from_warehouse_id']);
        }
        if (isset($filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $filters['to_warehouse_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Create a draft stock transfer with line items.
     *
     * No inventory movements are recorded at this stage.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $refNumber = $this->referenceNumberService->generate(
                $data['tenant_id'],
                'stock_transfer',
                null,
                'TRF-'
            );

            $transfer = StockTransfer::create([
                'tenant_id' => $data['tenant_id'],
                'reference_number' => $refNumber,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                StockTransferLine::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'cost_per_unit' => $line['cost_per_unit'] ?? '0',
                    'batch_number' => $line['batch_number'] ?? null,
                    'lot_number' => $line['lot_number'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: StockTransfer::class,
                auditableId: $transfer->id,
                newValues: [
                    'reference_number' => $transfer->reference_number,
                    'from_warehouse_id' => $transfer->from_warehouse_id,
                    'to_warehouse_id' => $transfer->to_warehouse_id,
                ]
            );

            return $transfer->fresh(['fromWarehouse', 'toWarehouse', 'lines.product']);
        });
    }

    /**
     * Dispatch a draft transfer (set in_transit and deduct from source warehouse).
     */
    public function dispatch(string $tenantId, string $id): StockTransfer
    {
        return DB::transaction(function () use ($tenantId, $id) {
            $transfer = StockTransfer::where('tenant_id', $tenantId)
                ->where('status', 'draft')
                ->findOrFail($id);

            foreach ($transfer->lines as $line) {
                $this->inventoryService->adjust(
                    tenantId: $tenantId,
                    warehouseId: $transfer->from_warehouse_id,
                    productId: $line->product_id,
                    quantity: $line->quantity,
                    movementType: 'transfer_out',
                    variantId: $line->variant_id,
                    notes: 'Transfer dispatched: '.$transfer->reference_number,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    batchNumber: $line->batch_number,
                    lotNumber: $line->lot_number,
                    expiryDate: $line->expiry_date ? \Carbon\Carbon::parse($line->expiry_date) : null,
                );
            }

            $transfer->update([
                'status' => 'in_transit',
                'transferred_at' => now(),
            ]);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: StockTransfer::class,
                auditableId: $transfer->id,
                newValues: ['status' => 'in_transit']
            );

            return $transfer->fresh(['fromWarehouse', 'toWarehouse', 'lines']);
        });
    }

    /**
     * Receive a dispatched transfer (set received and add to destination warehouse).
     */
    public function receive(string $tenantId, string $id): StockTransfer
    {
        return DB::transaction(function () use ($tenantId, $id) {
            $transfer = StockTransfer::where('tenant_id', $tenantId)
                ->where('status', 'in_transit')
                ->findOrFail($id);

            foreach ($transfer->lines as $line) {
                $this->inventoryService->adjust(
                    tenantId: $tenantId,
                    warehouseId: $transfer->to_warehouse_id,
                    productId: $line->product_id,
                    quantity: $line->quantity,
                    movementType: 'receipt',
                    variantId: $line->variant_id,
                    notes: 'Transfer received: '.$transfer->reference_number,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    batchNumber: $line->batch_number,
                    lotNumber: $line->lot_number,
                    expiryDate: $line->expiry_date ? \Carbon\Carbon::parse($line->expiry_date) : null,
                );
            }

            $transfer->update(['status' => 'received']);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: StockTransfer::class,
                auditableId: $transfer->id,
                newValues: ['status' => 'received']
            );

            return $transfer->fresh(['fromWarehouse', 'toWarehouse', 'lines']);
        });
    }

    /**
     * Cancel a draft or in_transit transfer.
     *
     * If the transfer is in_transit, the stock deducted on dispatch is reversed.
     */
    public function cancel(string $tenantId, string $id): StockTransfer
    {
        return DB::transaction(function () use ($tenantId, $id) {
            $transfer = StockTransfer::where('tenant_id', $tenantId)
                ->whereIn('status', ['draft', 'in_transit'])
                ->findOrFail($id);

            // Reverse the outbound movements if already dispatched
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->lines as $line) {
                    $this->inventoryService->adjust(
                        tenantId: $tenantId,
                        warehouseId: $transfer->from_warehouse_id,
                        productId: $line->product_id,
                        quantity: $line->quantity,
                        movementType: 'receipt',
                        variantId: $line->variant_id,
                        notes: 'Transfer cancelled (reversal): '.$transfer->reference_number,
                        referenceType: StockTransfer::class,
                        referenceId: $transfer->id,
                    );
                }
            }

            $transfer->update(['status' => 'cancelled']);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: StockTransfer::class,
                auditableId: $transfer->id,
                newValues: ['status' => 'cancelled']
            );

            return $transfer->fresh(['fromWarehouse', 'toWarehouse', 'lines']);
        });
    }
}
