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

    /**
     * @param  Collection<int, SalesDelivery>  $deliveries
     */
    private function refreshDeliveryStatuses(Collection $deliveries): void
    {
        foreach ($deliveries as $delivery) {
            $delivery->load('lines');
            $delivered = $this->math->sum($delivery->lines->pluck('delivered_quantity')->all());
            $invoiced = $this->math->sum($delivery->lines->pluck('invoiced_quantity')->all());
            $delivery->status = $this->math->compare($invoiced, $delivered) >= 0
                ? SalesDeliveryStatus::Invoiced
                : SalesDeliveryStatus::PartiallyInvoiced;
            $delivery->save();
        }
    }
}
