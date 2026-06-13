<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseOrderQuantityService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function applyReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $line->received_quantity = $this->math->add((string) $line->received_quantity, $quantity);
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->cancelled_quantity);
        $line->remaining_quantity = $remaining;
        $line->remaining_receivable_quantity = $remaining;
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero($remaining)
            ? PurchaseOrderLineStatus::Received
            : PurchaseOrderLineStatus::PartiallyReceived;
        $line->save();

        $this->refreshOrderStatus($line->order);
    }

    public function applyInvoiced(PurchaseOrderLine $line, string $quantity): void
    {
        $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
        $invoiceableBasis = $this->math->compare((string) $line->received_quantity, '0.000000') > 0
            ? (string) $line->received_quantity
            : (string) $line->ordered_quantity;
        $line->remaining_invoiceable_quantity = $this->math->sub(
            $invoiceableBasis,
            (string) $line->invoiced_quantity,
        );

        if ($this->math->compare((string) $line->invoiced_quantity, (string) $line->ordered_quantity) >= 0) {
            $line->status = PurchaseOrderLineStatus::Invoiced;
        } elseif ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            $line->status = PurchaseOrderLineStatus::PartiallyInvoiced;
        }

        $line->save();
        $this->refreshOrderStatus($line->order);
    }

    public function applyReturned(PurchaseOrderLine $line, string $quantity): void
    {
        $line->returned_quantity = $this->math->add((string) $line->returned_quantity, $quantity);
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $line->save();

        $this->refreshOrderStatus($line->order);
    }

    public function reverseReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $line->received_quantity = $this->math->sub((string) $line->received_quantity, $quantity);
        if ($this->math->isNegative((string) $line->received_quantity)) {
            $line->received_quantity = '0.000000';
        }

        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $line->remaining_quantity = $remaining;
        $line->remaining_receivable_quantity = $remaining;
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->received_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero((string) $line->received_quantity)
            ? PurchaseOrderLineStatus::Open
            : PurchaseOrderLineStatus::PartiallyReceived;
        $line->save();

        $this->refreshOrderStatus($line->order);
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
        $basis = $this->math->compare((string) $line->received_quantity, '0.000000') > 0
            ? (string) $line->received_quantity
            : (string) $line->ordered_quantity;

        return $this->math->compare(
            $this->math->sub($basis, (string) $line->invoiced_quantity),
            '0.000000',
        ) > 0;
    }

    private function refreshOrderStatus(?PurchaseOrder $order): void
    {
        if (! $order instanceof PurchaseOrder) {
            return;
        }

        $order->load('lines');
        $ordered = $this->math->sum($order->lines->pluck('ordered_quantity')->all());
        $received = $this->math->sum($order->lines->pluck('received_quantity')->all());
        $invoiced = $this->math->sum($order->lines->pluck('invoiced_quantity')->all());
        $returned = $this->math->sum($order->lines->pluck('returned_quantity')->all());

        if ($this->math->compare($returned, $ordered) >= 0) {
            $order->status = PurchaseOrderStatus::Returned;
        } elseif ($this->math->compare($returned, '0.000000') > 0) {
            $order->status = PurchaseOrderStatus::PartiallyReturned;
        } elseif ($this->math->compare($invoiced, $ordered) >= 0) {
            $order->status = PurchaseOrderStatus::Invoiced;
        } elseif ($this->math->compare($invoiced, '0.000000') > 0) {
            $order->status = PurchaseOrderStatus::PartiallyInvoiced;
        } elseif ($this->math->compare($received, $ordered) >= 0) {
            $order->status = PurchaseOrderStatus::Received;
        } elseif ($this->math->compare($received, '0.000000') > 0) {
            $order->status = PurchaseOrderStatus::PartiallyReceived;
        }

        $order->save();
    }
}
