<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;

final class InvoiceStatusService
{
    /**
     * @return array<string, list<string>>
     */
    private function transitions(): array
    {
        return [
            InvoiceStatus::Draft->value => [
                InvoiceStatus::Approved->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Approved->value => [
                InvoiceStatus::Posted->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Posted->value => [
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Paid->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::PartiallyPaid->value => [
                InvoiceStatus::Paid->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Paid->value => [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Cancelled->value => [],
            InvoiceStatus::Void->value => [],
        ];
    }

    public function assertCanTransition(InvoiceStatus|string $from, InvoiceStatus|string $to): void
    {
        $fromValue = $from instanceof InvoiceStatus ? $from->value : $from;
        $toValue = $to instanceof InvoiceStatus ? $to->value : $to;

        if (! in_array($toValue, $this->transitions()[$fromValue] ?? [], true)) {
            throw new InvalidArgumentException(sprintf('Invoice status cannot transition from %s to %s.', $fromValue, $toValue));
        }
    }

    public function transition(Invoice $invoice, InvoiceStatus $to): Invoice
    {
        $from = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);

        $this->assertCanTransition($from, $to);

        $updates = ['status' => $to->value];
        if ($to === InvoiceStatus::Approved) {
            $updates['approved_at'] = now();
        }
        if ($to === InvoiceStatus::Posted) {
            $updates['posted_at'] = now();
        }

        $invoice->forceFill($updates)->save();

        return $invoice->refresh();
    }

    public function assertEditable(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);

        if ($status !== InvoiceStatus::Draft) {
            throw new InvalidArgumentException('Only draft invoices can be edited directly.');
        }
    }

    public function assertCanSettle(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);

        if (in_array($status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
            throw new InvalidArgumentException('Cancelled or void invoices cannot be settled.');
        }
    }
}
