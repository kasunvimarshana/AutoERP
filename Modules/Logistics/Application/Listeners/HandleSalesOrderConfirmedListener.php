<?php

namespace Modules\Logistics\Application\Listeners;

use Modules\Logistics\Application\UseCases\CreateDeliveryOrderUseCase;
use Modules\Sales\Domain\Events\OrderConfirmed;


class HandleSalesOrderConfirmedListener
{
    public function __construct(
        private CreateDeliveryOrderUseCase $createDelivery,
    ) {}

    public function handle(OrderConfirmed $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        // Build delivery lines from order lines, filtering out invalid entries
        $deliveryLines = [];
        foreach ($event->lines as $line) {
            $productId = $line['product_id'] ?? null;
            $qty       = (string) ($line['qty'] ?? '0');

            if ($productId === null) {
                continue;
            }

            if (bccomp($qty, '0', 8) <= 0) {
                continue;
            }

            $deliveryLines[] = [
                'product_id'   => $productId,
                'product_name' => $line['description'] ?? '',
                'quantity'     => $qty,
                'unit'         => $line['uom'] ?? 'pcs',
            ];
        }

        if (empty($deliveryLines)) {
            return;
        }

        try {
            $this->createDelivery->execute([
                'tenant_id'          => $event->tenantId,
                'reference_no'       => null, // auto-generated inside CreateDeliveryOrderUseCase
                'destination_address' => null,
                'scheduled_date'     => $event->promisedDeliveryDate,
                'notes'              => 'Auto-created from sales order ' . $event->orderId,
                'lines'              => $deliveryLines,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a logistics creation failure must never
            // prevent the sales order from being confirmed.
        }
    }
}
