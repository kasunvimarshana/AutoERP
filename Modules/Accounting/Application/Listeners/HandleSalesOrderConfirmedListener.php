<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Sales\Domain\Events\OrderConfirmed;


class HandleSalesOrderConfirmedListener
{
    public function __construct(
        private CreateInvoiceUseCase $createInvoice,
    ) {}

    public function handle(OrderConfirmed $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        // Build invoice lines from order lines, filtering out invalid entries.
        // unit_price is required for invoice creation; lines missing it are skipped.
        $invoiceLines = [];
        foreach ($event->lines as $line) {
            $productId  = $line['product_id'] ?? null;
            $qty        = (string) ($line['qty'] ?? '0');
            $unitPrice  = isset($line['unit_price']) ? (string) $line['unit_price'] : null;

            if ($productId === null) {
                continue;
            }

            if (bccomp($qty, '0', 8) <= 0) {
                continue;
            }

            if ($unitPrice === null) {
                continue;
            }

            $invoiceLines[] = [
                'product_id'  => $productId,
                'description' => $line['description'] ?? '',
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'tax_rate'    => isset($line['tax_rate']) ? (string) $line['tax_rate'] : '0',
            ];
        }

        if (empty($invoiceLines)) {
            return;
        }

        try {
            $this->createInvoice->execute([
                'tenant_id'    => $event->tenantId,
                'invoice_type' => 'customer_invoice',
                'partner_id'   => $event->customerId,
                'partner_type' => 'customer',
                'currency'     => 'USD',
                'notes'        => 'Auto-created from sales order ' . $event->orderId,
                'lines'        => $invoiceLines,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: an accounting creation failure must never
            // prevent the sales order from being confirmed.
        }
    }
}
