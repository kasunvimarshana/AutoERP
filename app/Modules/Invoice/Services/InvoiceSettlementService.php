<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\SettlementResultData;
use Modules\Invoice\Models\Invoice;

final class InvoiceSettlementService implements InvoiceSettlementServiceInterface
{
    public function __construct(private readonly InvoiceBalanceService $balances) {}

    public function applyPaymentAllocation(int $invoiceId, string $amount, bool $allowOverpayment = false): SettlementResultData
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        if ($invoice->balance === null) {
            throw new InvalidArgumentException('Invoice balance record is missing.');
        }
        $before = (string) $invoice->balance->remaining_amount;
        $balance = $this->balances->applyPayment($invoice, $amount, $allowOverpayment);
        $balanceBefore = (string) ($balance->getAttribute('previous_remaining_amount') ?? $before);

        return new SettlementResultData(
            sourceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id,
            settledAmount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: (string) $balance->remaining_amount,
            status: $balance->status instanceof \BackedEnum
                ? (string) $balance->status->value
                : (string) $balance->status,
            sourceType: 'invoice',
        );
    }

    public function reversePaymentAllocation(int $invoiceId, string $amount): SettlementResultData
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        if ($invoice->balance === null) {
            throw new InvalidArgumentException('Invoice balance record is missing.');
        }
        $before = (string) $invoice->balance->remaining_amount;
        $balance = $this->balances->reversePayment($invoice, $amount);
        $balanceBefore = (string) ($balance->getAttribute('previous_remaining_amount') ?? $before);

        return new SettlementResultData(
            sourceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id,
            settledAmount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: (string) $balance->remaining_amount,
            status: $balance->status instanceof \BackedEnum
                ? (string) $balance->status->value
                : (string) $balance->status,
            sourceType: 'invoice',
        );
    }
}
