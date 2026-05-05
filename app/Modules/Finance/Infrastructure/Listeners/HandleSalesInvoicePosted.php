<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Sales\Domain\Events\SalesInvoicePosted;

class HandleSalesInvoicePosted
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(SalesInvoicePosted $event): void
    {
        if ($event->arAccountId === null) {
            Log::warning('HandleSalesInvoicePosted: AR account not configured; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (empty($event->lines)) {
            Log::warning('HandleSalesInvoicePosted: no invoice lines in event; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        // Aggregate credit amounts by income_account_id (revenue accounts)
        $creditsByAccount = [];
        foreach ($event->lines as $line) {
            $accountId = isset($line['income_account_id']) ? (int) $line['income_account_id'] : null;
            if ($accountId === null) {
                Log::warning('HandleSalesInvoicePosted: invoice line missing income_account_id; skipping journal entry', [
                    'sales_invoice_id' => $event->salesInvoiceId,
                    'line' => $line,
                ]);

                return;
            }

            $amount = bcadd((string) ($line['line_total'] ?? '0'), (string) ($line['tax_amount'] ?? '0'), 6);
            $creditsByAccount[$accountId] = bcadd($creditsByAccount[$accountId] ?? '0.000000', $amount, 6);
        }

        $grandTotal = $event->grandTotal;
        $creditTotal = array_reduce(array_values($creditsByAccount), fn (string $carry, string $a): string => bcadd($carry, $a, 6), '0.000000');

        if (bccomp($creditTotal, $grandTotal, 2) !== 0) {
            Log::warning('HandleSalesInvoicePosted: credit total does not match grand total; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'credit_total' => $creditTotal,
                'grand_total' => $grandTotal,
            ]);

            return;
        }

        $invoiceDate = $event->invoiceDate !== ''
            ? new \DateTimeImmutable($event->invoiceDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $invoiceDate);
        if ($period === null) {
            Log::warning('HandleSalesInvoicePosted: no open fiscal period for invoice date; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'invoice_date' => $event->invoiceDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $exchangeRate = $event->exchangeRate;
        $description = 'AR entry for Sales Invoice #'.$event->salesInvoiceId;

        $jeLines = [];

        // DR: AR account = grandTotal
        $baseGrandTotal = bcmul($grandTotal, $exchangeRate, 6);
        $jeLines[] = [
            'account_id' => $event->arAccountId,
            'debit_amount' => $grandTotal,
            'credit_amount' => '0.000000',
            'description' => $description,
            'currency_id' => $event->currencyId,
            'exchange_rate' => (float) $exchangeRate,
            'base_debit_amount' => $baseGrandTotal,
            'base_credit_amount' => '0.000000',
        ];

        // CR: revenue account(s) per line
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

        DB::transaction(function () use ($event, $period, $invoiceDate, $description, $jeLines): void {
            $this->createJournalEntryService->execute([
                'tenant_id' => $event->tenantId,
                'fiscal_period_id' => $period->getId(),
                'entry_date' => $invoiceDate->format('Y-m-d'),
                'created_by' => $event->createdBy ?: 1,
                'entry_type' => 'system',
                'reference_type' => 'sales_invoice',
                'reference_id' => $event->salesInvoiceId,
                'description' => $description,
                'lines' => $jeLines,
            ]);
        });
    }
}
