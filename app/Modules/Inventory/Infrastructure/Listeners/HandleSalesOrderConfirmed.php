<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;
use Modules\Sales\Domain\Events\SalesOrderConfirmed;

class HandleSalesOrderConfirmed
{
    public function __construct(
        private readonly StockReservationRepositoryInterface $stockReservationRepository,
    ) {}

    public function handle(SalesOrderConfirmed $event): void
    {
        if (empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId  = (int) $line['product_id'];
            $variantId  = isset($line['variant_id']) ? (int) $line['variant_id'] : null;
            $qtyNeeded  = (string) $line['quantity'];

            if (bccomp($qtyNeeded, '0', 6) <= 0) {
                continue;
            }

            // Find stock level rows for this product in the warehouse, ordered by
            // available quantity descending so we fill from the fullest location first.
            $stockRows = DB::table('stock_levels')
                ->join('warehouse_locations', 'warehouse_locations.id', '=', 'stock_levels.location_id')
                ->where('stock_levels.tenant_id', $event->tenantId)
                ->where('stock_levels.product_id', $productId)
                ->where(function ($q) use ($variantId): void {
                    if ($variantId !== null) {
                        $q->where('stock_levels.variant_id', $variantId);
                    } else {
                        $q->whereNull('stock_levels.variant_id');
                    }
                })
                ->where('warehouse_locations.warehouse_id', $event->warehouseId)
                ->selectRaw('stock_levels.id, stock_levels.location_id, stock_levels.uom_id,
                             stock_levels.quantity_on_hand, stock_levels.quantity_reserved,
                             (CAST(stock_levels.quantity_on_hand AS REAL) - CAST(stock_levels.quantity_reserved AS REAL)) AS available_raw')
                ->orderByDesc('available_raw')
                ->get();

            foreach ($stockRows as $row) {
                if (bccomp($qtyNeeded, '0', 6) <= 0) {
                    break;
                }

                $available = bcsub((string) $row->quantity_on_hand, (string) $row->quantity_reserved, 6);
                if (bccomp($available, '0', 6) <= 0) {
                    continue;
                }

                $toReserve = bccomp($available, $qtyNeeded, 6) >= 0 ? $qtyNeeded : $available;

                try {
                    $this->stockReservationRepository->create(new StockReservation(
                        tenantId: $event->tenantId,
                        productId: $productId,
                        variantId: $variantId,
                        batchId: null,
                        serialId: null,
                        locationId: (int) $row->location_id,
                        quantity: $toReserve,
                        reservedForType: 'sales_orders',
                        reservedForId: $event->salesOrderId,
                        expiresAt: null,
                    ));
                } catch (\Throwable $e) {
                    Log::warning('HandleSalesOrderConfirmed: reservation failed for location', [
                        'sales_order_id' => $event->salesOrderId,
                        'product_id'     => $productId,
                        'location_id'    => $row->location_id,
                        'to_reserve'     => $toReserve,
                        'error'          => $e->getMessage(),
                    ]);
                    continue;
                }

                $qtyNeeded = bcsub($qtyNeeded, $toReserve, 6);
            }

            if (bccomp($qtyNeeded, '0', 6) > 0) {
                Log::warning('HandleSalesOrderConfirmed: partial or no stock reservation — insufficient available stock', [
                    'sales_order_id'   => $event->salesOrderId,
                    'product_id'       => $productId,
                    'variant_id'       => $variantId,
                    'qty_unreserved'   => $qtyNeeded,
                    'warehouse_id'     => $event->warehouseId,
                ]);
            }
        }
    }
}
