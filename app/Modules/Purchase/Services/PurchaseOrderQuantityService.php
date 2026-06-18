<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseOrderQuantityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseStatusService $statuses,
    ) {}

    public function applyReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $line = $this->lockLine($line);
        $line->received_quantity = $this->math->add((string) $line->received_quantity, $quantity);
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->cancelled_quantity);
        $this->assertNonNegative($remaining, 'Purchase order receivable quantity cannot be negative.');
        $line->remaining_quantity = $remaining;
        $line->remaining_receivable_quantity = $remaining;
        $line->remaining_invoiceable_quantity = $this->math->sub(
            $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity),
            (string) $line->invoiced_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_invoiceable_quantity, 'Purchase order invoiceable quantity cannot be negative.');
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_returnable_quantity, 'Purchase order returnable quantity cannot be negative.');
        $line->status = $this->math->isZero($remaining)
            ? PurchaseOrderLineStatus::Received
            : PurchaseOrderLineStatus::PartiallyReceived;
        $line->save();
        $this->refreshOrderFor($line);
    }

    public function applyInvoiced(PurchaseOrderLine $line, string $quantity): void
    {
        $line = $this->lockLine($line);
        $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->ordered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->remaining_invoiceable_quantity,
            (string) $line->cancelled_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_invoiceable_quantity, 'Purchase order invoiceable quantity cannot be negative.');

        if ($this->math->compare((string) $line->invoiced_quantity, (string) $line->ordered_quantity) >= 0) {
            $line->status = PurchaseOrderLineStatus::Invoiced;
        } elseif ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            $line->status = PurchaseOrderLineStatus::PartiallyInvoiced;
        }

        $line->save();
        $this->refreshOrderFor($line);
    }

    public function reverseInvoiced(PurchaseOrderLine $line, string $quantity): void
    {
        $line = $this->lockLine($line);
        if ($this->math->compare($quantity, (string) $line->invoiced_quantity) > 0) {
            throw new InvalidArgumentException('Cannot reverse more invoiced quantity than currently applied.');
        }

        $line->invoiced_quantity = $this->math->sub((string) $line->invoiced_quantity, $quantity);
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->ordered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->remaining_invoiceable_quantity,
            (string) $line->cancelled_quantity,
        );
        $line->status = $this->lineStatus($line);
        $line->save();
        $this->refreshOrderFor($line);
    }

    public function applyReturned(PurchaseOrderLine $line, string $quantity): void
    {
        $line = $this->lockLine($line);
        $line->returned_quantity = $this->math->add((string) $line->returned_quantity, $quantity);
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_returnable_quantity, 'Purchase order returnable quantity cannot be negative.');
        $line->save();
        $this->refreshOrderFor($line);
    }

    public function reverseReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $line = $this->lockLine($line);
        if ($this->math->compare($quantity, (string) $line->received_quantity) > 0) {
            throw new InvalidArgumentException('Cannot reverse more received quantity than currently applied.');
        }

        $line->received_quantity = $this->math->sub((string) $line->received_quantity, $quantity);

        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->cancelled_quantity);
        $this->assertNonNegative($remaining, 'Purchase order receivable quantity cannot be negative.');
        $line->remaining_quantity = $remaining;
        $line->remaining_receivable_quantity = $remaining;
        $line->remaining_invoiceable_quantity = $this->math->sub(
            $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity),
            (string) $line->invoiced_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_invoiceable_quantity, 'Purchase order invoiceable quantity cannot be negative.');
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $this->assertNonNegative((string) $line->remaining_returnable_quantity, 'Purchase order returnable quantity cannot be negative.');
        $line->status = $this->math->isZero((string) $line->received_quantity)
            ? PurchaseOrderLineStatus::Open
            : PurchaseOrderLineStatus::PartiallyReceived;
        $line->save();
        $this->refreshOrderFor($line);
    }

    public function isReceivable(PurchaseOrderLine $line): bool
    {
        return $this->math->compare(
            (string) $line->remaining_receivable_quantity,
            '0.000000',
        ) > 0;
    }

    public function isInvoiceable(PurchaseOrderLine $line): bool
    {
        $basis = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);

        return $this->math->compare(
            $this->math->sub($basis, (string) $line->invoiced_quantity),
            '0.000000',
        ) > 0;
    }

    private function lineStatus(PurchaseOrderLine $line): PurchaseOrderLineStatus
    {
        if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->invoiced_quantity,
                (string) $line->ordered_quantity,
            ) >= 0
                ? PurchaseOrderLineStatus::Invoiced
                : PurchaseOrderLineStatus::PartiallyInvoiced;
        }

        if ($this->math->compare((string) $line->received_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->received_quantity,
                (string) $line->ordered_quantity,
            ) >= 0
                ? PurchaseOrderLineStatus::Received
                : PurchaseOrderLineStatus::PartiallyReceived;
        }

        return PurchaseOrderLineStatus::Open;
    }

    private function assertNonNegative(string $quantity, string $message): void
    {
        if ($this->math->isNegative($quantity)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function lockLine(PurchaseOrderLine $line): PurchaseOrderLine
    {
        return PurchaseOrderLine::query()->lockForUpdate()->findOrFail($line->getKey());
    }

    private function refreshOrderFor(PurchaseOrderLine $line): void
    {
        $order = PurchaseOrder::query()->with('lines')->find($line->purchase_order_id);
        if ($order instanceof PurchaseOrder) {
            $this->statuses->refreshPurchaseOrder($order);
        }
    }
}
