<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrderLine;

final class SalesInvoiceQuantityUpdater
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesOrderQuantityService $orderQuantities,
    ) {}

    /**
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, SalesDelivery>  $deliveries
     */
    public function apply(array $lineQuantities, Collection $deliveries): void
    {
        foreach ($lineQuantities as $lineKey => $quantity) {
            [$lineType, $lineId] = explode(':', (string) $lineKey, 2);

            if ($lineType === 'sales_delivery_line') {
                $this->applyDeliveryQuantity((int) $lineId, $quantity);

                continue;
            }

            if ($lineType === 'sales_order_line') {
                $this->orderQuantities->applyInvoiced(
                    SalesOrderLine::query()->findOrFail((int) $lineId),
                    $quantity,
                );
            }
        }

        $this->refreshDeliveryStatuses($deliveries);
    }

    /**
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, SalesDelivery>  $deliveries
     */
    public function reverse(array $lineQuantities, Collection $deliveries): void
    {
        foreach ($lineQuantities as $lineKey => $quantity) {
            [$lineType, $lineId] = explode(':', (string) $lineKey, 2);

            if ($lineType === 'sales_delivery_line') {
                $this->reverseDeliveryQuantity((int) $lineId, $quantity);

                continue;
            }

            if ($lineType === 'sales_order_line') {
                $line = SalesOrderLine::query()->lockForUpdate()->findOrFail((int) $lineId);
                $this->orderQuantities->reverseInvoiced($line, $quantity);
            }
        }

        $this->refreshDeliveryStatuses($deliveries);
    }

    private function applyDeliveryQuantity(int $lineId, string $quantity): void
    {
        $line = SalesDeliveryLine::query()
            ->with('salesOrderLine')
            ->findOrFail($lineId);
        $line->invoiced_quantity = $this->math->add(
            (string) $line->invoiced_quantity,
            $quantity,
        );
        $line->remaining_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->math->isZero((string) $line->remaining_quantity)
            ? 'invoiced'
            : 'partially_invoiced';
        $line->save();

        if ($line->salesOrderLine instanceof SalesOrderLine) {
            $this->orderQuantities->applyInvoiced($line->salesOrderLine, $quantity);
        }
    }

    private function reverseDeliveryQuantity(int $lineId, string $quantity): void
    {
        $line = SalesDeliveryLine::query()
            ->with('salesOrderLine')
            ->lockForUpdate()
            ->findOrFail($lineId);
        $line->invoiced_quantity = $this->subtractToZero(
            (string) $line->invoiced_quantity,
            $quantity,
        );
        $line->remaining_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->deliveryLineStatus($line);
        $line->save();

        if ($line->salesOrderLine instanceof SalesOrderLine) {
            $this->orderQuantities->reverseInvoiced($line->salesOrderLine, $quantity);
        }
    }

    /**
     * @param  Collection<int, SalesDelivery>  $deliveries
     */
    private function refreshDeliveryStatuses(Collection $deliveries): void
    {
        foreach ($deliveries as $delivery) {
            $delivery->load('lines');
            $delivered = $this->math->sum($delivery->lines->pluck('delivered_quantity')->all());
            $invoiced = $this->math->sum($delivery->lines->pluck('invoiced_quantity')->all());
            $returned = $this->math->sum($delivery->lines->pluck('returned_quantity')->all());
            $delivery->status = match (true) {
                $this->math->compare($returned, $delivered) >= 0 => SalesDeliveryStatus::Returned,
                $this->math->compare($returned, '0.000000') > 0 => SalesDeliveryStatus::PartiallyReturned,
                $this->math->compare($invoiced, $delivered) >= 0 => SalesDeliveryStatus::Invoiced,
                $this->math->compare($invoiced, '0.000000') > 0 => SalesDeliveryStatus::PartiallyInvoiced,
                default => SalesDeliveryStatus::Posted,
            };
            $delivery->save();
        }
    }

    private function deliveryLineStatus(SalesDeliveryLine $line): string
    {
        if ($this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->returned_quantity,
                (string) $line->delivered_quantity,
            ) >= 0 ? 'returned' : 'partially_returned';
        }

        if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->invoiced_quantity,
                (string) $line->delivered_quantity,
            ) >= 0 ? 'invoiced' : 'partially_invoiced';
        }

        return 'posted';
    }

    private function subtractToZero(string $current, string $quantity): string
    {
        $result = $this->math->sub($current, $quantity);

        return $this->math->isNegative($result) ? '0.000000' : $result;
    }
}
