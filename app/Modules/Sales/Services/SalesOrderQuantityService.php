<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Sales\Enums\SalesOrderLineStatus;
use Modules\Sales\Enums\SalesOrderStatus;
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

        $this->refreshOrder($line->order);
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

        $this->refreshOrder($line->order);
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

        $this->refreshOrder($line->order);
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

        $this->refreshOrder($line->order);
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

        $this->refreshOrder($line->order);
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

    private function refreshOrder(?SalesOrder $order): void
    {
        if (! $order instanceof SalesOrder
            || in_array($order->status, [SalesOrderStatus::Closed, SalesOrderStatus::Cancelled], true)) {
            return;
        }

        $order->load('lines');
        $ordered = $this->math->sum($order->lines->pluck('ordered_quantity')->all());
        $allocated = $this->math->sum($order->lines->pluck('allocated_quantity')->all());
        $delivered = $this->math->sum($order->lines->pluck('delivered_quantity')->all());
        $invoiced = $this->math->sum($order->lines->pluck('invoiced_quantity')->all());
        $returned = $this->math->sum($order->lines->pluck('returned_quantity')->all());

        $order->status = match (true) {
            $this->math->compare($returned, $ordered) >= 0 => SalesOrderStatus::Returned,
            $this->isPositive($returned) => SalesOrderStatus::PartiallyReturned,
            $this->math->compare($invoiced, $ordered) >= 0 => SalesOrderStatus::Invoiced,
            $this->isPositive($invoiced) => SalesOrderStatus::PartiallyInvoiced,
            $this->math->compare($delivered, $ordered) >= 0 => SalesOrderStatus::Delivered,
            $this->isPositive($delivered) => SalesOrderStatus::PartiallyDelivered,
            $this->math->compare($allocated, $ordered) >= 0 => SalesOrderStatus::Allocated,
            $this->isPositive($allocated) => SalesOrderStatus::PartiallyAllocated,
            default => $this->openStatus($order->status),
        };
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

    private function isPositive(string $value): bool
    {
        return $this->math->compare($value, '0.000000') > 0;
    }

    private function openStatus(SalesOrderStatus $status): SalesOrderStatus
    {
        return in_array($status, [
            SalesOrderStatus::Draft,
            SalesOrderStatus::PendingApproval,
            SalesOrderStatus::Approved,
        ], true) ? $status : SalesOrderStatus::Approved;
    }
}
