<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;

class HandleGoodsReceiptPosted
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(GoodsReceiptPosted $event): void
    {
        if ($event->apAccountId === null) {
            Log::warning('HandleGoodsReceiptPosted: AP account not configured; skipping journal entry', [
                'grn_header_id' => $event->grnHeaderId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (empty($event->lines)) {
            Log::warning('HandleGoodsReceiptPosted: no GRN lines in event; skipping journal entry', [
                'grn_header_id' => $event->grnHeaderId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if ($this->journalAlreadyPosted($event->tenantId, 'grn', $event->grnHeaderId)) {
            Log::info('HandleGoodsReceiptPosted: replay detected; journal entry already exists, skipping', [
                'grn_header_id' => $event->grnHeaderId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        // Aggregate debit amounts by inventory_account_id
        $debitsByAccount = [];
        foreach ($event->lines as $line) {
            $inventoryAccountId = isset($line['inventory_account_id'])
                ? (int) $line['inventory_account_id']
                : null;

            if ($inventoryAccountId === null || $inventoryAccountId <= 0) {
                Log::warning('HandleGoodsReceiptPosted: GRN line missing inventory_account_id; skipping', [
                    'grn_header_id' => $event->grnHeaderId,
                    'line' => $line,
                ]);

                return;
            }

            $lineTotal = bcmul((string) ($line['unit_cost'] ?? '0'), (string) ($line['received_qty'] ?? '0'), 6);
            $debitsByAccount[$inventoryAccountId] = bcadd(
                $debitsByAccount[$inventoryAccountId] ?? '0.000000',
                $lineTotal,
                6
            );
        }

        $grandTotal = array_reduce(
            array_values($debitsByAccount),
            fn (string $carry, string $a): string => bcadd($carry, $a, 6),
            '0.000000'
        );

        if (bccomp($grandTotal, '0.000000', 6) <= 0) {
            return;
        }

        $receivedDate = $event->receivedDate !== ''
            ? new \DateTimeImmutable($event->receivedDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $receivedDate);
        if ($period === null) {
            Log::warning('HandleGoodsReceiptPosted: no open fiscal period for received date; skipping', [
                'grn_header_id' => $event->grnHeaderId,
                'received_date' => $event->receivedDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $exchangeRate = $event->exchangeRate;
        $description = 'GRN accrual for Goods Receipt #'.$event->grnHeaderId;

        $jeLines = [];
        foreach ($debitsByAccount as $accountId => $amount) {
            $baseAmount = bcmul($amount, $exchangeRate, 6);
            $jeLines[] = [
                'account_id' => $accountId,
                'debit_amount' => $amount,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseAmount,
                'base_credit_amount' => '0.000000',
            ];
        }

        $baseGrandTotal = bcmul($grandTotal, $exchangeRate, 6);
        $jeLines[] = [
            'account_id' => $event->apAccountId,
            'debit_amount' => '0.000000',
            'credit_amount' => $grandTotal,
            'description' => $description,
            'currency_id' => $event->currencyId,
            'exchange_rate' => (float) $exchangeRate,
            'base_debit_amount' => '0.000000',
            'base_credit_amount' => $baseGrandTotal,
        ];

        try {
            DB::transaction(function () use ($event, $period, $receivedDate, $description, $jeLines): void {
                $this->createJournalEntryService->execute([
                    'tenant_id' => $event->tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date' => $receivedDate->format('Y-m-d'),
                    'created_by' => $event->createdBy ?: 1,
                    'entry_type' => 'system',
                    'reference_type' => 'grn',
                    'reference_id' => $event->grnHeaderId,
                    'description' => $description,
                    'lines' => $jeLines,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict(
                $exception,
                [
                    'journal_entries_tenant_reference_uk',
                    'journal_entries.tenant_id, journal_entries.reference_type, journal_entries.reference_id',
                ]
            )) {
                throw $exception;
            }

            if (! $this->journalAlreadyPosted($event->tenantId, 'grn', $event->grnHeaderId)) {
                throw new \RuntimeException(
                    'HandleGoodsReceiptPosted: replay conflict with incomplete artifacts for grn #'
                        .$event->grnHeaderId,
                    0,
                    $exception
                );
            }

            Log::info('HandleGoodsReceiptPosted: duplicate-key replay conflict detected; skipping', [
                'grn_header_id' => $event->grnHeaderId,
                'tenant_id' => $event->tenantId,
            ]);
        }
    }
}
