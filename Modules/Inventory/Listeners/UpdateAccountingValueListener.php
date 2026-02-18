<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Inventory\Events\StockMovementRecorded;

/**
 * Update Accounting Value Listener
 *
 * Updates accounting inventory value when stock movements occur.
 * Creates journal entries for inventory valuation changes.
 * Implements event-driven integration between Inventory and Accounting modules.
 */
class UpdateAccountingValueListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Create a new listener instance
     */
    public function __construct(
        private JournalEntryRepositoryInterface $journalEntryRepository,
        private JournalEntryLineRepositoryInterface $journalEntryLineRepository,
        private AccountRepositoryInterface $accountRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(StockMovementRecorded $event): void
    {
        DB::beginTransaction();
        try {
            $movement = $event->stockMovement;

            // Only process movements that affect inventory value
            if (! $this->shouldCreateJournalEntry($movement->movement_type)) {
                return;
            }

            // Calculate inventory value change
            $unitCost = $movement->unit_cost ?? $movement->product->cost_price ?? 0;
            $totalValue = abs($movement->quantity) * $unitCost;

            // Get or create inventory asset account
            $inventoryAccount = $this->accountRepository->findByCode('1400') // Inventory Asset
                ?? $this->createInventoryAccount($movement->tenant_id);

            // Determine contra account based on movement type
            $contraAccount = $this->getContraAccount($movement->movement_type, $movement->tenant_id);

            // Create journal entry
            $journalEntry = $this->journalEntryRepository->create([
                'tenant_id' => $movement->tenant_id,
                'entry_number' => $this->generateEntryNumber(),
                'entry_date' => $movement->created_at ?? now(),
                'entry_type' => 'inventory_adjustment',
                'reference_type' => 'stock_movement',
                'reference_id' => $movement->id,
                'reference_number' => $movement->reference_number,
                'description' => "Inventory valuation for {$movement->movement_type}: {$movement->reference_number}",
                'status' => 'posted',
            ]);

            // Create journal entry lines (double-entry bookkeeping)
            if ($movement->quantity > 0) {
                // Stock increase: Debit Inventory, Credit contra account
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $movement->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $inventoryAccount->id,
                    'description' => "Inventory increase - {$movement->product->name}",
                    'debit_amount' => $totalValue,
                    'credit_amount' => 0,
                ]);

                $this->journalEntryLineRepository->create([
                    'tenant_id' => $movement->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $contraAccount->id,
                    'description' => "Inventory increase - {$movement->product->name}",
                    'debit_amount' => 0,
                    'credit_amount' => $totalValue,
                ]);
            } else {
                // Stock decrease: Credit Inventory, Debit contra account
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $movement->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $contraAccount->id,
                    'description' => "Inventory decrease - {$movement->product->name}",
                    'debit_amount' => $totalValue,
                    'credit_amount' => 0,
                ]);

                $this->journalEntryLineRepository->create([
                    'tenant_id' => $movement->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $inventoryAccount->id,
                    'description' => "Inventory decrease - {$movement->product->name}",
                    'debit_amount' => 0,
                    'credit_amount' => $totalValue,
                ]);
            }

            DB::commit();

            Log::info('Accounting journal entry created for stock movement', [
                'movement_id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'product_id' => $movement->product_id,
                'quantity' => $movement->quantity,
                'total_value' => $totalValue,
                'journal_entry_id' => $journalEntry->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create accounting journal entry for stock movement', [
                'movement_id' => $event->stockMovement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Determine if journal entry should be created for this movement type
     */
    private function shouldCreateJournalEntry(string $movementType): bool
    {
        return in_array($movementType, [
            'RECEIPT',      // Goods received
            'ISSUE',        // Goods issued/sold
            'ADJUSTMENT',   // Inventory adjustment
            'RETURN',       // Purchase return
            'TRANSFER',     // Warehouse transfer
        ]);
    }

    /**
     * Get contra account based on movement type
     */
    private function getContraAccount(string $movementType, string $tenantId)
    {
        $accountCode = match ($movementType) {
            'RECEIPT' => '2100',      // Accounts Payable
            'ISSUE' => '5000',        // Cost of Goods Sold
            'ADJUSTMENT' => '6100',   // Inventory Adjustment Expense
            'RETURN' => '2100',       // Accounts Payable
            'TRANSFER' => '1400',     // Inventory Asset (same account, different location)
            default => '6100',        // Default to adjustment expense
        };

        return $this->accountRepository->findByCode($accountCode)
            ?? $this->createDefaultAccount($accountCode, $tenantId);
    }

    /**
     * Create inventory account if it doesn't exist
     */
    private function createInventoryAccount(string $tenantId)
    {
        return $this->accountRepository->create([
            'tenant_id' => $tenantId,
            'code' => '1400',
            'name' => json_encode(['en' => 'Inventory Asset']),
            'account_type' => 'asset',
            'account_subtype' => 'current_asset',
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    /**
     * Create default account if it doesn't exist
     */
    private function createDefaultAccount(string $code, string $tenantId)
    {
        $accountDefinitions = [
            '2100' => ['name' => 'Accounts Payable', 'type' => 'liability', 'subtype' => 'current_liability'],
            '5000' => ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'subtype' => 'cost_of_sales'],
            '6100' => ['name' => 'Inventory Adjustment', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ];

        $definition = $accountDefinitions[$code] ?? ['name' => 'Other Account', 'type' => 'expense', 'subtype' => 'other'];

        return $this->accountRepository->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => json_encode(['en' => $definition['name']]),
            'account_type' => $definition['type'],
            'account_subtype' => $definition['subtype'],
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    /**
     * Generate unique journal entry number
     */
    private function generateEntryNumber(): string
    {
        $prefix = 'JE';
        $date = now()->format('Ymd');
        $random = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Handle a job failure
     */
    public function failed(StockMovementRecorded $event, \Throwable $exception): void
    {
        Log::error('Accounting journal entry creation failed permanently', [
            'movement_id' => $event->stockMovement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
