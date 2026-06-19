<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrderLine;

final class SalesFulfilmentBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function salesOrderAllocatableRemainingSql(string $table = 'sales_order_lines'): string
    {
        return "({$table}.ordered_quantity - {$table}.cancelled_quantity - {$table}.allocated_quantity)";
    }

    public function salesOrderDeliverableRemainingSql(string $table = 'sales_order_lines'): string
    {
        return "({$table}.ordered_quantity - {$table}.cancelled_quantity - {$table}.delivered_quantity)";
    }

    public function salesOrderInvoiceableRemainingSql(string $table = 'sales_order_lines'): string
    {
        return "((case when {$table}.delivered_quantity > 0 then {$table}.delivered_quantity else {$table}.ordered_quantity - {$table}.cancelled_quantity end) - {$table}.invoiced_quantity)";
    }

    public function salesOrderReturnableRemainingSql(string $table = 'sales_order_lines'): string
    {
        return "({$table}.delivered_quantity - {$table}.returned_quantity)";
    }

    public function salesDeliveryInvoiceableRemainingSql(string $table = 'sales_delivery_lines'): string
    {
        return "({$table}.delivered_quantity - {$table}.invoiced_quantity)";
    }

    public function salesDeliveryReturnableRemainingSql(string $table = 'sales_delivery_lines'): string
    {
        return "({$table}.delivered_quantity - {$table}.returned_quantity)";
    }

    public function whereSalesOrderLineAllocatable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesOrderAllocatableRemainingSql().' > 0');
    }

    public function whereSalesOrderLineDeliverable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesOrderDeliverableRemainingSql().' > 0');
    }

    public function whereSalesOrderLineInvoiceable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesOrderInvoiceableRemainingSql().' > 0');
    }

    public function whereSalesOrderLineReturnable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesOrderReturnableRemainingSql().' > 0');
    }

    public function whereSalesDeliveryLineInvoiceable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesDeliveryInvoiceableRemainingSql().' > 0');
    }

    public function whereSalesDeliveryLineReturnable(Builder $query): Builder
    {
        return $query->whereRaw($this->salesDeliveryReturnableRemainingSql().' > 0');
    }

    public function remainingAllocatableForSalesOrderLine(SalesOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->allocated_quantity);

        return $this->nonNegative($remaining);
    }

    public function remainingDeliverableForSalesOrderLine(SalesOrderLine $line): string
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->delivered_quantity);

        return $this->nonNegative($remaining);
    }

    public function remainingInvoiceableForSalesOrderLine(SalesOrderLine $line): string
    {
        $basis = $this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
            ? (string) $line->delivered_quantity
            : $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);

        return $this->nonNegative($this->math->sub($basis, (string) $line->invoiced_quantity));
    }

    public function remainingReturnableForSalesOrderLine(SalesOrderLine $line): string
    {
        return $this->nonNegative($this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity));
    }

    public function remainingInvoiceableForSalesDeliveryLine(SalesDeliveryLine $line): string
    {
        return $this->nonNegative($this->math->sub((string) $line->delivered_quantity, (string) $line->invoiced_quantity));
    }

    public function remainingReturnableForSalesDeliveryLine(SalesDeliveryLine $line): string
    {
        return $this->nonNegative($this->math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity));
    }

    public function allocationStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'ordered_quantity', 'cancelled_quantity', 'allocated_quantity', 'none', 'partial', 'complete');
    }

    public function deliveryStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'ordered_quantity', 'cancelled_quantity', 'delivered_quantity', 'none', 'partial', 'complete');
    }

    public function salesOrderInvoiceStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'ordered_quantity', 'cancelled_quantity', 'invoiced_quantity', 'none', 'partial', 'complete');
    }

    public function salesOrderReturnStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'delivered_quantity', null, 'returned_quantity', 'none', 'partial', 'complete');
    }

    public function salesDeliveryInvoiceStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'delivered_quantity', null, 'invoiced_quantity', 'none', 'partial', 'complete');
    }

    public function salesDeliveryReturnStatus(iterable $lines): string
    {
        return $this->quantityProgress($lines, 'delivered_quantity', null, 'returned_quantity', 'none', 'partial', 'complete');
    }

    private function nonNegative(string $value): string
    {
        return $this->math->isNegative($value) ? '0.000000' : $this->math->normalize($value);
    }

    private function quantityProgress(
        iterable $lines,
        string $basisColumn,
        ?string $cancelledColumn,
        string $progressColumn,
        string $none,
        string $partial,
        string $complete,
    ): string {
        $basis = '0.000000';
        $progress = '0.000000';

        foreach ($lines as $line) {
            $lineBasis = (string) ($line->{$basisColumn} ?? '0.000000');
            if ($cancelledColumn !== null) {
                $lineBasis = $this->math->sub($lineBasis, (string) ($line->{$cancelledColumn} ?? '0.000000'));
            }
            $basis = $this->math->add($basis, $lineBasis);
            $progress = $this->math->add($progress, (string) ($line->{$progressColumn} ?? '0.000000'));
        }

        if ($this->math->compare($progress, '0.000000') <= 0 || $this->math->compare($basis, '0.000000') <= 0) {
            return $none;
        }

        return $this->math->compare($progress, $basis) >= 0 ? $complete : $partial;
    }
}
