<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Sales\Enums\SalesOrderLineStatus;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

final class SalesOrderQuantityService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function applyAllocated(SalesOrderLine $line, string $quantity, ?int $allocationId): void
    {
        $line->allocated_quantity = $this->math->add((string) $line->allocated_quantity, $quantity);
        $line->remaining_allocatable_quantity = $this->math->sub(
            (string) $line->ordered_quantity,
            (string) $line->allocated_quantity,
        );
        $line->inventory_allocation_id = $allocationId;
        $line->status = $this->math->isZero((string) $line->remaining_allocatable_quantity)
            ? SalesOrderLineStatus::Allocated
            : SalesOrderLineStatus::PartiallyAllocated;
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function applyDelivered(SalesOrderLine $line, string $quantity): void
    {
        $line->delivered_quantity = $this->math->add((string) $line->delivered_quantity, $quantity);
        $line->remaining_deliverable_quantity = $this->math->sub(
            $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity),
            (string) $line->delivered_quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero((string) $line->remaining_deliverable_quantity)
            ? SalesOrderLineStatus::Delivered
            : SalesOrderLineStatus::PartiallyDelivered;
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function reverseDelivered(SalesOrderLine $line, string $quantity): void
    {
        $line->delivered_quantity = $this->math->sub((string) $line->delivered_quantity, $quantity);
        $line->allocated_quantity = $this->math->sub((string) $line->allocated_quantity, $quantity);
        $line->remaining_deliverable_quantity = $this->math->sub(
            (string) $line->ordered_quantity,
            (string) $line->delivered_quantity,
        );
        $line->remaining_allocatable_quantity = $this->math->sub(
            (string) $line->ordered_quantity,
            (string) $line->allocated_quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero((string) $line->delivered_quantity)
            ? SalesOrderLineStatus::Open
            : SalesOrderLineStatus::PartiallyDelivered;
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function applyInvoiced(SalesOrderLine $line, string $quantity): void
    {
        $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
        $basis = $this->invoiceableBasis($line);
        $line->remaining_invoiceable_quantity = $this->math->sub(
            $basis,
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->math->isZero((string) $line->remaining_invoiceable_quantity)
            ? SalesOrderLineStatus::Invoiced
            : SalesOrderLineStatus::PartiallyInvoiced;
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function reverseInvoiced(SalesOrderLine $line, string $quantity): void
    {
        $line->invoiced_quantity = $this->subtractToZero(
            (string) $line->invoiced_quantity,
            $quantity,
        );
        $line->remaining_invoiceable_quantity = $this->math->sub(
            $this->invoiceableBasis($line),
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->lineStatus($line);
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function applyReturned(SalesOrderLine $line, string $quantity): void
    {
        $line->returned_quantity = $this->math->add((string) $line->returned_quantity, $quantity);
        $line->remaining_returnable_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero((string) $line->remaining_returnable_quantity)
            ? SalesOrderLineStatus::Returned
            : SalesOrderLineStatus::PartiallyReturned;
        $line->save();

        $this->refreshOrderTotals($line->order);
    }

    public function isAllocatable(SalesOrderLine $line): bool
    {
        return $this->isPositive((string) $line->remaining_allocatable_quantity);
    }

    public function isDeliverable(SalesOrderLine $line): bool
    {
        return $this->isPositive((string) $line->remaining_deliverable_quantity);
    }

    public function isInvoiceable(SalesOrderLine $line): bool
    {
        return $this->isPositive(
            $this->math->sub($this->invoiceableBasis($line), (string) $line->invoiced_quantity),
        );
    }

    private function refreshOrderTotals(?SalesOrder $order): void
    {
        if (! $order instanceof SalesOrder) {
            return;
        }

        $order->load('lines');
        $order->allocated_total = $this->amountForQuantity($order, 'allocated_quantity');
        $order->delivered_total = $this->amountForQuantity($order, 'delivered_quantity');
        $order->invoiced_total = $this->amountForQuantity($order, 'invoiced_quantity');
        $order->returned_total = $this->amountForQuantity($order, 'returned_quantity');
        $order->save();
    }

    private function amountForQuantity(SalesOrder $order, string $column): string
    {
        $total = '0.000000';
        foreach ($order->lines as $line) {
            if ($this->math->isZero((string) $line->ordered_quantity)) {
                continue;
            }
            $ratio = $this->math->div(
                (string) $line->{$column},
                (string) $line->ordered_quantity,
                12,
            );
            $total = $this->math->add(
                $total,
                $this->math->mul((string) $line->line_total, $ratio),
            );
        }

        return $total;
    }

    private function invoiceableBasis(SalesOrderLine $line): string
    {
        return $this->isPositive((string) $line->delivered_quantity)
            ? (string) $line->delivered_quantity
            : (string) $line->ordered_quantity;
    }

    private function lineStatus(SalesOrderLine $line): SalesOrderLineStatus
    {
        if ($this->isPositive((string) $line->returned_quantity)) {
            return $this->math->compare(
                (string) $line->returned_quantity,
                (string) $line->delivered_quantity,
            ) >= 0
                ? SalesOrderLineStatus::Returned
                : SalesOrderLineStatus::PartiallyReturned;
        }

        if ($this->isPositive((string) $line->invoiced_quantity)) {
            return $this->math->compare(
                (string) $line->invoiced_quantity,
                $this->invoiceableBasis($line),
            ) >= 0
                ? SalesOrderLineStatus::Invoiced
                : SalesOrderLineStatus::PartiallyInvoiced;
        }

        if ($this->isPositive((string) $line->delivered_quantity)) {
            return $this->math->compare(
                (string) $line->delivered_quantity,
                (string) $line->ordered_quantity,
            ) >= 0
                ? SalesOrderLineStatus::Delivered
                : SalesOrderLineStatus::PartiallyDelivered;
        }

        if ($this->isPositive((string) $line->allocated_quantity)) {
            return $this->math->compare(
                (string) $line->allocated_quantity,
                (string) $line->ordered_quantity,
            ) >= 0
                ? SalesOrderLineStatus::Allocated
                : SalesOrderLineStatus::PartiallyAllocated;
        }

        return SalesOrderLineStatus::Open;
    }

    private function subtractToZero(string $current, string $quantity): string
    {
        $result = $this->math->sub($current, $quantity);

        return $this->math->isNegative($result) ? '0.000000' : $result;
    }

    private function isPositive(string $value): bool
    {
        return $this->math->compare($value, '0.000000') > 0;
    }
}
