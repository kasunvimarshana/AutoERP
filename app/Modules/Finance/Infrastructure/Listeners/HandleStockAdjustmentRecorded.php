<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Inventory\Domain\Events\StockAdjustmentRecorded;

class HandleStockAdjustmentRecorded
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(StockAdjustmentRecorded $event): void
    {
        if ($event->inventoryAccountId === null || $event->expenseAccountId === null) {
            Log::warning('HandleStockAdjustmentRecorded: product account mappings missing; skipping journal entry', [
                'tenant_id' => $event->tenantId,
                'stock_movement_id' => $event->stockMovementId,
                'product_id' => $event->productId,
            ]);

            return;
        }

        if (bccomp($event->amount, '0.000000', 6) <= 0) {
            return;
        }

        $movementDate = $event->movementDate !== ''
            ? new \DateTimeImmutable($event->movementDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $movementDate);
        if ($period === null) {
            Log::warning('HandleStockAdjustmentRecorded: no open fiscal period; skipping journal entry', [
                'tenant_id' => $event->tenantId,
                'stock_movement_id' => $event->stockMovementId,
                'movement_date' => $event->movementDate,
            ]);

            return;
        }

        $amount = (float) $event->amount;
        $description = 'Inventory adjustment for Stock Movement #'.$event->stockMovementId;

        $debitAccountId = $event->movementType === 'adjustment_in'
            ? $event->inventoryAccountId
            : $event->expenseAccountId;

        $creditAccountId = $event->movementType === 'adjustment_in'
            ? $event->expenseAccountId
            : $event->inventoryAccountId;

        $this->createJournalEntryService->execute([
            'tenant_id' => $event->tenantId,
            'fiscal_period_id' => $period->getId(),
            'entry_date' => $movementDate->format('Y-m-d'),
            'created_by' => $event->createdBy > 0 ? $event->createdBy : 1,
            'entry_type' => 'system',
            'reference_type' => 'stock_movement',
            'reference_id' => $event->stockMovementId,
            'description' => $description,
            'lines' => [
                [
                    'account_id' => $debitAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0.0,
                    'description' => $description,
                    'currency_id' => 1,
                    'exchange_rate' => 1.0,
                    'base_debit_amount' => $amount,
                    'base_credit_amount' => 0.0,
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit_amount' => 0.0,
                    'credit_amount' => $amount,
                    'description' => $description,
                    'currency_id' => 1,
                    'exchange_rate' => 1.0,
                    'base_debit_amount' => 0.0,
                    'base_credit_amount' => $amount,
                ],
            ],
        ]);
    }
}
