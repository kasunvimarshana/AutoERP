<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;

class HandlePurchaseReturnPosted
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly CreateApTransactionServiceInterface $createApTransactionService,
        private readonly ApTransactionRepositoryInterface $apTransactionRepository,
    ) {}

    public function handle(PurchaseReturnPosted $event): void
    {
        if ($event->apAccountId === null) {
            Log::warning('HandlePurchaseReturnPosted: AP account not configured; skipping journal entry', [
                'purchase_return_id' => $event->purchaseReturnId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (bccomp($event->grandTotal, '0.000000', 6) <= 0) {
            Log::warning('HandlePurchaseReturnPosted: zero grand total; skipping journal entry', [
                'purchase_return_id' => $event->purchaseReturnId,
            ]);

            return;
        }

        // Aggregate credit amounts by account_id (inventory / expense accounts being reversed)
        $creditsByAccount = [];
        foreach ($event->lines as $line) {
            $accountId = isset($line['account_id']) ? (int) $line['account_id'] : null;
            if ($accountId === null) {
                continue;
            }

            $amount = bcadd((string) ($line['line_total'] ?? '0'), (string) ($line['tax_amount'] ?? '0'), 6);
            $creditsByAccount[$accountId] = bcadd($creditsByAccount[$accountId] ?? '0.000000', $amount, 6);
        }

        $grandTotal = $event->grandTotal;

        $returnDate = $event->returnDate !== ''
            ? new \DateTimeImmutable($event->returnDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $returnDate);
        if ($period === null) {
            Log::warning('HandlePurchaseReturnPosted: no open fiscal period for return date; skipping journal entry', [
                'purchase_return_id' => $event->purchaseReturnId,
                'return_date' => $event->returnDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $exchangeRate = $event->exchangeRate;
        $description = 'AP reversal for Purchase Return #'.$event->purchaseReturnId;

        DB::transaction(function () use ($event, $period, $returnDate, $description, $grandTotal, $exchangeRate, $creditsByAccount): void {
            $jeLines = [];

            // DR: Accounts Payable (reduces the amount owed to supplier)
            $baseGrandTotal = bcmul($grandTotal, $exchangeRate, 6);
            $jeLines[] = [
                'account_id' => $event->apAccountId,
                'debit_amount' => $grandTotal,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseGrandTotal,
                'base_credit_amount' => '0.000000',
            ];

            // CR: Inventory/Expense accounts (reverses original purchase entries)
            if (! empty($creditsByAccount)) {
                foreach ($creditsByAccount as $accountId => $amount) {
                    $baseAmount = bcmul($amount, $exchangeRate, 6);
                    $jeLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => '0.000000',
                        'credit_amount' => $amount,
                        'description' => $description,
                        'currency_id' => $event->currencyId,
                        'exchange_rate' => (float) $exchangeRate,
                        'base_debit_amount' => '0.000000',
                        'base_credit_amount' => $baseAmount,
                    ];
                }
            } else {
                // Fallback: single balancing credit entry against AP account if no line accounts are available
                $jeLines[] = [
                    'account_id' => $event->apAccountId,
                    'debit_amount' => '0.000000',
                    'credit_amount' => $grandTotal,
                    'description' => $description.' (offset)',
                    'currency_id' => $event->currencyId,
                    'exchange_rate' => (float) $exchangeRate,
                    'base_debit_amount' => '0.000000',
                    'base_credit_amount' => $baseGrandTotal,
                ];
            }

            $this->createJournalEntryService->execute([
                'tenant_id' => $event->tenantId,
                'fiscal_period_id' => $period->getId(),
                'entry_date' => $returnDate->format('Y-m-d'),
                'created_by' => $event->createdBy ?: 1,
                'entry_type' => 'system',
                'reference_type' => 'purchase_return',
                'reference_id' => $event->purchaseReturnId,
                'description' => $description,
                'lines' => $jeLines,
            ]);

            // Record AP credit transaction (reduces supplier balance)
            $currentBalance = (float) $this->apTransactionRepository
                ->getSupplierBalance($event->tenantId, $event->supplierId);

            $newBalance = (float) bcsub((string) $currentBalance, $grandTotal, 6);

            $this->createApTransactionService->execute([
                'tenant_id' => $event->tenantId,
                'supplier_id' => $event->supplierId,
                'account_id' => $event->apAccountId,
                'transaction_type' => 'debit_note',
                'amount' => -1 * (float) $grandTotal,
                'balance_after' => $newBalance,
                'transaction_date' => $returnDate->format('Y-m-d'),
                'currency_id' => $event->currencyId,
                'reference_type' => 'purchase_return',
                'reference_id' => $event->purchaseReturnId,
            ]);
        });
    }
}
