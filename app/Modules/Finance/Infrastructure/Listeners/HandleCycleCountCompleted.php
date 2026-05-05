<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\Inventory\Domain\Events\CycleCountCompleted;

class HandleCycleCountCompleted
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(CycleCountCompleted $event): void
    {
        if (empty($event->adjustments)) {
            return;
        }

        if ($this->journalAlreadyPosted($event->tenantId, 'cycle_count', $event->cycleCountId)) {
            Log::info('HandleCycleCountCompleted: replay detected; journal entry already exists, skipping', [
                'tenant_id' => $event->tenantId,
                'cycle_count_id' => $event->cycleCountId,
            ]);

            return;
        }

        $countDate = $event->countDate !== ''
            ? new \DateTimeImmutable($event->countDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $countDate);
        if ($period === null) {
            Log::warning('HandleCycleCountCompleted: no open fiscal period; skipping journal entry', [
                'tenant_id' => $event->tenantId,
                'cycle_count_id' => $event->cycleCountId,
                'count_date' => $event->countDate,
            ]);

            return;
        }

        $debitsByAccount = [];
        $creditsByAccount = [];

        foreach ($event->adjustments as $adjustment) {
            $inventoryAccountId = isset($adjustment['inventory_account_id']) ? (int) $adjustment['inventory_account_id'] : 0;
            $expenseAccountId = isset($adjustment['expense_account_id']) ? (int) $adjustment['expense_account_id'] : 0;
            $direction = (string) ($adjustment['direction'] ?? '');
            $amount = (string) ($adjustment['amount'] ?? '0.000000');

            if ($inventoryAccountId <= 0 || $expenseAccountId <= 0 || bccomp($amount, '0.000000', 6) <= 0) {
                continue;
            }

            if ($direction === 'increase') {
                $debitsByAccount[$inventoryAccountId] = bcadd($debitsByAccount[$inventoryAccountId] ?? '0.000000', $amount, 6);
                $creditsByAccount[$expenseAccountId] = bcadd($creditsByAccount[$expenseAccountId] ?? '0.000000', $amount, 6);

                continue;
            }

            if ($direction === 'decrease') {
                $debitsByAccount[$expenseAccountId] = bcadd($debitsByAccount[$expenseAccountId] ?? '0.000000', $amount, 6);
                $creditsByAccount[$inventoryAccountId] = bcadd($creditsByAccount[$inventoryAccountId] ?? '0.000000', $amount, 6);
            }
        }

        if (empty($debitsByAccount) || empty($creditsByAccount)) {
            Log::warning('HandleCycleCountCompleted: no valid account mappings for adjustments; skipping journal entry', [
                'tenant_id' => $event->tenantId,
                'cycle_count_id' => $event->cycleCountId,
            ]);

            return;
        }

        $description = 'Inventory adjustment for Cycle Count #'.$event->cycleCountId;
        $jeLines = [];

        foreach ($debitsByAccount as $accountId => $amount) {
            $numericAmount = (float) $amount;
            $jeLines[] = [
                'account_id' => $accountId,
                'debit_amount' => $numericAmount,
                'credit_amount' => 0.0,
                'description' => $description,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
                'base_debit_amount' => $numericAmount,
                'base_credit_amount' => 0.0,
            ];
        }

        foreach ($creditsByAccount as $accountId => $amount) {
            $numericAmount = (float) $amount;
            $jeLines[] = [
                'account_id' => $accountId,
                'debit_amount' => 0.0,
                'credit_amount' => $numericAmount,
                'description' => $description,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
                'base_debit_amount' => 0.0,
                'base_credit_amount' => $numericAmount,
            ];
        }

        try {
            $this->createJournalEntryService->execute([
                'tenant_id' => $event->tenantId,
                'fiscal_period_id' => $period->getId(),
                'entry_date' => $countDate->format('Y-m-d'),
                'created_by' => $event->createdBy > 0 ? $event->createdBy : 1,
                'entry_type' => 'system',
                'reference_type' => 'cycle_count',
                'reference_id' => $event->cycleCountId,
                'description' => $description,
                'lines' => $jeLines,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception)) {
                throw $exception;
            }

            if (! $this->journalAlreadyPosted($event->tenantId, 'cycle_count', $event->cycleCountId)) {
                throw new \RuntimeException(
                    'HandleCycleCountCompleted: replay conflict detected with missing journal artifact for cycle_count_id '.$event->cycleCountId,
                    0,
                    $exception
                );
            }

            Log::info('HandleCycleCountCompleted: duplicate-key replay conflict detected; skipping', [
                'tenant_id' => $event->tenantId,
                'cycle_count_id' => $event->cycleCountId,
            ]);
        }
    }

}
