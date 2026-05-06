<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;

class HandleGoodsReceiptPosted
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $inventoryStockRepository,
        private readonly TraceLogRepositoryInterface $traceLogRepository,
    ) {}

    public function handle(GoodsReceiptPosted $event): void
    {
        if (empty($event->lines)) {
            return;
        }

        DB::transaction(function () use ($event): void {
            foreach ($event->lines as $line) {
                $locationId = $line['location_id'] ?? null;
                if ($locationId === null) {
                    Log::warning('HandleGoodsReceiptPosted: missing location_id for GRN line', [
                        'grn_header_id' => $event->grnHeaderId,
                        'line' => $line,
                    ]);

                    continue;
                }

                $movement = new StockMovement(
                    tenantId: $event->tenantId,
                    productId: (int) $line['product_id'],
                    variantId: isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                    batchId: isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                    serialId: isset($line['serial_id']) ? (int) $line['serial_id'] : null,
                    fromLocationId: null,
                    toLocationId: (int) $locationId,
                    movementType: 'receipt',
                    referenceType: 'grn_header',
                    referenceId: $event->grnHeaderId,
                    uomId: (int) $line['uom_id'],
                    quantity: (string) $line['received_qty'],
                    unitCost: isset($line['unit_cost']) ? (string) $line['unit_cost'] : null,
                    performedBy: null,
                    performedAt: new \DateTimeImmutable,
                    notes: 'GRN #'.$event->grnHeaderId.' posted',
                    metadata: null,
                );

                $saved = $this->inventoryStockRepository->recordMovement($movement);

                try {
                    $this->inventoryStockRepository->adjustStockLevel($saved);
                } catch (InsufficientAvailableStockException $e) {
                    Log::warning('HandleGoodsReceiptPosted: stock level adjustment failed for GRN line', [
                        'grn_header_id' => $event->grnHeaderId,
                        'product_id'    => $line['product_id'],
                        'location_id'   => $locationId,
                        'received_qty'  => $line['received_qty'],
                    ]);
                }

                $this->traceLogRepository->recordForMovement($saved);
            }
        });
    }
}
