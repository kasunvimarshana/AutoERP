<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Invoice\DTOs\InvoiceBalanceResult;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Models\Invoice;

final class InvoiceBalanceProvider implements InvoiceBalanceProviderInterface
{
    public function __construct(
        private readonly InvoiceStatusService $statuses,
    ) {}

    public function getInvoiceReferences(array $invoiceIds): array
    {
        return Invoice::query()
            ->whereIn('id', array_values(array_unique($invoiceIds)))
            ->get(['id', 'invoice_number'])
            ->mapWithKeys(fn (Invoice $invoice): array => [
                (int) $invoice->getKey() => [
                    'id' => (int) $invoice->getKey(),
                    'invoice_number' => $invoice->invoice_number,
                    'name' => $invoice->invoice_number ?? 'Invoice',
                ],
            ])
            ->all();
    }

    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $balance = $invoice->balance;

        return new InvoiceBalanceResult(
            invoiceId: (int) $invoice->getKey(),
            invoiceTotal: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            debitAmount: (string) $balance->debit_allocated_amount,
            refundedAmount: (string) $balance->refunded_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: $balance->status instanceof InvoiceBalanceStatus
                ? $balance->status
                : InvoiceBalanceStatus::from((string) $balance->status),
        );
    }

    public function getBalance(int $invoiceId): BalanceResultData
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $balance = $invoice->balance;

        return new BalanceResultData(
            sourceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id,
            totalAmount: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: (string) (
                $balance->status instanceof InvoiceBalanceStatus
                    ? $balance->status->value
                    : $balance->status
            ),
            partyType: $invoice->party_type,
            partyId: $invoice->party_id,
            currencyId: $invoice->currency_id,
            sourceType: 'invoice',
        );
    }

    public function getInvoiceStatus(int $invoiceId): string
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        return $invoice->status instanceof \BackedEnum
            ? (string) $invoice->status->value
            : (string) $invoice->status;
    }

    public function getPayableBalancesForParty(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
    ): array {
        $invoiceQuery = Invoice::query()
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.party_type', $partyType)
            ->where('invoices.party_id', $partyId)
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereNotIn('invoices.status', ['draft', 'cancelled', 'void'])
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.id');

        $organizationUnitId === null
            ? $invoiceQuery->whereNull('invoices.organization_unit_id')
            : $invoiceQuery->where('invoices.organization_unit_id', $organizationUnitId);

        return $invoiceQuery
            ->pluck('invoices.id')
            ->map(fn (mixed $invoiceId): BalanceResultData => $this->getBalance((int) $invoiceId))
            ->all();
    }

    public function validatePayableState(int $invoiceId): BalanceResultData
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $this->statuses->assertCanSettle($invoice);

        return $this->getBalance($invoiceId);
    }
}
