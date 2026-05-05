<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Sales\Domain\Events\SalesReturnReceived;

class HandleSalesReturnReceived
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly CreateArTransactionServiceInterface $createArTransactionService,
        private readonly ArTransactionRepositoryInterface $arTransactionRepository,
    ) {}

    public function handle(SalesReturnReceived $event): void
    {
        if ($event->arAccountId === null) {
            Log::warning('HandleSalesReturnReceived: AR account not configured; skipping journal entry', [
                'sales_return_id' => $event->salesReturnId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (bccomp($event->grandTotal, '0.000000', 6) <= 0) {
            Log::warning('HandleSalesReturnReceived: zero grand total; skipping journal entry', [
                'sales_return_id' => $event->salesReturnId,
            ]);

            return;
        }

        // Aggregate debit amounts by income_account_id (revenue accounts being reversed)
        $debitsByAccount = [];
        foreach ($event->lines as $line) {
            $accountId = isset($line['income_account_id']) ? (int) $line['income_account_id'] : null;
            if ($accountId === null) {
                continue;
            }

            $amount = (string) ($line['line_total'] ?? '0');
            $existing = $debitsByAccount[$accountId] ?? '0.000000';
            $debitsByAccount[$accountId] = bcadd($existing, $amount, 6);
        }

        $grandTotal = $event->grandTotal;

        $returnDate = $event->returnDate !== ''
            ? new \DateTimeImmutable($event->returnDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $returnDate);
        if ($period === null) {
            Log::warning('HandleSalesReturnReceived: no open fiscal period for return date; skipping journal entry', [
                'sales_return_id' => $event->salesReturnId,
                'return_date' => $event->returnDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $exchangeRate = $event->exchangeRate;
        $description = 'AR reversal for Sales Return #'.$event->salesReturnId;

        DB::transaction(function () use ($event, $period, $returnDate, $description, $grandTotal, $exchangeRate, $debitsByAccount): void {
            $jeLines = [];

            // CR: Accounts Receivable (reduces amount owed by customer — credit note)
            $baseGrandTotal = bcmul($grandTotal, $exchangeRate, 6);
            $jeLines[] = [
                'account_id' => $event->arAccountId,
                'debit_amount' => '0.000000',
                'credit_amount' => $grandTotal,
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => '0.000000',
                'base_credit_amount' => $baseGrandTotal,
            ];

            // DR: Revenue/Income accounts (reverses original sale revenue)
            if (! empty($debitsByAccount)) {
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
            } else {
                // Fallback: single balancing debit entry against AR account if no line accounts available
                $jeLines[] = [
                    'account_id' => $event->arAccountId,
                    'debit_amount' => $grandTotal,
                    'credit_amount' => '0.000000',
                    'description' => $description.' (offset)',
                    'currency_id' => $event->currencyId,
                    'exchange_rate' => (float) $exchangeRate,
                    'base_debit_amount' => $baseGrandTotal,
                    'base_credit_amount' => '0.000000',
                ];
            }

            $this->createJournalEntryService->execute([
                'tenant_id' => $event->tenantId,
                'fiscal_period_id' => $period->getId(),
                'entry_date' => $returnDate->format('Y-m-d'),
                'created_by' => $event->createdBy ?: 1,
                'entry_type' => 'system',
                'reference_type' => 'sales_return',
                'reference_id' => $event->salesReturnId,
                'description' => $description,
                'lines' => $jeLines,
            ]);

            // Record AR debit note transaction (reduces customer receivable balance)
            $currentBalance = $this->arTransactionRepository
                ->getCustomerBalance($event->tenantId, $event->customerId);

            $newBalance = bcsub($currentBalance, $grandTotal, 6);

            $this->createArTransactionService->execute([
                'tenant_id' => $event->tenantId,
                'customer_id' => $event->customerId,
                'account_id' => $event->arAccountId,
                'transaction_type' => 'credit_note',
                'amount' => -1 * (float) $grandTotal,
                'balance_after' => (float) $newBalance,
                'transaction_date' => $returnDate->format('Y-m-d'),
                'currency_id' => $event->currencyId,
                'reference_type' => 'sales_return',
                'reference_id' => $event->salesReturnId,
            ]);
        });
    }
}
