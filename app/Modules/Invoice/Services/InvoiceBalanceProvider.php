<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Core\DTOs\Integration\BalanceResultData;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\InvoiceBalanceResult;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Models\Invoice;

final class InvoiceBalanceProvider implements InvoiceBalanceProviderInterface
{
    public function __construct(
        private readonly InvoiceStatusService $statuses,
    ) {}

    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $balance = $invoice->balance;

        return new InvoiceBalanceResult(
            invoiceId: (int) $invoice->getKey(),
            invoiceTotal: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
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

    public function validatePayableState(int $invoiceId): BalanceResultData
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $this->statuses->assertCanSettle($invoice);

        return $this->getBalance($invoiceId);
    }
}
