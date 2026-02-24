<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Purchase\Domain\Events\GoodsReceived;


class HandleGoodsReceivedListener
{
    public function __construct(
        private CreateInvoiceUseCase $createInvoice,
    ) {}

    public function handle(GoodsReceived $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        // Build vendor bill lines from GRN lines, filtering out invalid entries.
        // unit_price is required for vendor bill creation; lines missing it are skipped.
        $billLines = [];
        foreach ($event->lines as $line) {
            $productId  = $line['product_id'] ?? null;
            $qty        = (string) ($line['qty_accepted'] ?? '0');
            $unitPrice  = isset($line['unit_price']) && $line['unit_price'] !== null
                ? (string) $line['unit_price']
                : null;

            if ($productId === null) {
                continue;
            }

            if (bccomp($qty, '0', 8) <= 0) {
                continue;
            }

            if ($unitPrice === null) {
                continue;
            }

            $billLines[] = [
                'product_id'  => $productId,
                'description' => $line['description'] ?? '',
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'tax_rate'    => isset($line['tax_rate']) ? (string) $line['tax_rate'] : '0',
            ];
        }

        if (empty($billLines)) {
            return;
        }

        try {
            $this->createInvoice->execute([
                'tenant_id'    => $event->tenantId,
                'invoice_type' => 'vendor_bill',
                'partner_id'   => $event->vendorId,
                'partner_type' => 'vendor',
                'currency'     => 'USD', // default; future: resolve from tenant/vendor/PO currency setting
                'notes'        => 'Auto-created from goods receipt ' . $event->grnId
                                . ' (PO: ' . $event->poId . ')',
                'lines'        => $billLines,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a vendor bill creation failure must never
            // prevent the GRN from being recorded.
        }
    }
}
