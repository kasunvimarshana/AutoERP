<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Events\InventoryAdjusted;
use Modules\Inventory\Models\InventoryTransaction;
use Modules\Inventory\Repositories\InventoryTransactionRepository;

/**
 * Inventory Service
 *
 * High-level service for inventory orchestration and transaction management
 */
class InventoryService
{
    public function __construct(
        private readonly InventoryItemService $inventoryItemService,
        private readonly InventoryTransactionRepository $transactionRepository
    ) {}

    /**
     * Adjust inventory with full transaction tracking
     *
     * @param  int  $itemId  Inventory item ID
     * @param  float  $quantity  Quantity to adjust (positive for add, negative for deduct)
     * @param  string  $transactionType  Type of transaction (e.g., 'job_card_usage', 'purchase', 'return')
     * @param  int|null  $referenceId  ID of the related record (e.g., job card ID)
     * @param  string  $reason  Reason for adjustment
     *
     * @throws ServiceException
     */
    public function adjustInventory(
        int $itemId,
        float $quantity,
        string $transactionType,
        ?int $referenceId = null,
        string $reason = ''
    ): InventoryTransaction {
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $item = $this->inventoryItemService->getById($itemId);

            if (! $item) {
                throw new ServiceException("Inventory item not found: {$itemId}");
            }

            // Check if adjustment would result in negative stock
            $newBalance = $item->stock_on_hand + $quantity;
            if ($newBalance < 0) {
                throw new ServiceException(
                    "Insufficient stock for item '{$item->item_name}'. ".
                    "Current: {$item->stock_on_hand}, Requested: ".abs($quantity)
                );
            }

            // Create transaction record
            $transaction = $this->transactionRepository->create([
                'inventory_item_id' => $itemId,
                'transaction_type' => $transactionType,
                'quantity' => $quantity,
                'balance_before' => $item->stock_on_hand,
                'balance_after' => $newBalance,
                'reference_type' => $referenceId ? 'job_card' : null,
                'reference_id' => $referenceId,
                'notes' => $reason,
                'created_by' => auth()->id(),
            ]);

            // Update item stock
            $this->inventoryItemService->update($itemId, [
                'stock_on_hand' => $newBalance,
            ]);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            // Dispatch event for async processing (notifications, alerts, etc.)
            event(new InventoryAdjusted($transaction, $reason));

            Log::info('Inventory adjusted successfully', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'transaction_type' => $transactionType,
                'new_balance' => $newBalance,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }

            Log::error('Failed to adjust inventory', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceException(
                "Failed to adjust inventory: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Bulk adjust inventory for multiple items
     *
     * @param  array<int, array{item_id: int, quantity: float, reason: string}>  $adjustments
     * @return array<int, InventoryTransaction>
     *
     * @throws ServiceException
     */
    public function bulkAdjustInventory(
        array $adjustments,
        string $transactionType,
        ?int $referenceId = null
    ): array {
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $transactions = [];

            foreach ($adjustments as $adjustment) {
                $transactions[] = $this->adjustInventory(
                    itemId: $adjustment['item_id'],
                    quantity: $adjustment['quantity'],
                    transactionType: $transactionType,
                    referenceId: $referenceId,
                    reason: $adjustment['reason'] ?? ''
                );
            }

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $transactions;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }
}
