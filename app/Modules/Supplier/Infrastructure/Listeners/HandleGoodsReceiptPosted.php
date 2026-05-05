<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;

/**
 * Updates supplier_products.last_purchase_price whenever a GRN is posted.
 *
 * For each product line on the receipt, if the supplier has a corresponding
 * supplier_products record (meaning this supplier is registered as a supplier
 * for that product), the last_purchase_price column is updated with the actual
 * unit cost from the GRN.  Rows that do not exist are silently skipped — this
 * listener never creates new supplier_product catalogue entries.
 */
class HandleGoodsReceiptPosted
{
    public function handle(GoodsReceiptPosted $event): void
    {
        if (empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId = (int) $line['product_id'];
            $variantId = isset($line['variant_id']) ? (int) $line['variant_id'] : null;
            $unitCost  = (string) ($line['unit_cost'] ?? '0');

            $query = DB::table('supplier_products')
                ->where('tenant_id', $event->tenantId)
                ->where('supplier_id', $event->supplierId)
                ->where('product_id', $productId);

            if ($variantId !== null) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }

            $query->update([
                'last_purchase_price' => $unitCost,
                'updated_at'          => now(),
            ]);
        }
    }
}
