<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;

class HandlePurchaseReturnPosted
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $inventoryStockRepository,
        private readonly TraceLogRepositoryInterface $traceLogRepository,
    ) {}

    public function handle(PurchaseReturnPosted $event): void
    {
        if (empty($event->lines)) {
            return;
        }

        DB::transaction(function () use ($event): void {
            foreach ($event->lines as $line) {
                $fromLocationId = $line['from_location_id'] ?? null;
                if ($fromLocationId === null) {
                    Log::warning('HandlePurchaseReturnPosted: missing from_location_id for return line', [
                        'purchase_return_id' => $event->purchaseReturnId,
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
                    fromLocationId: (int) $fromLocationId,
                    toLocationId: null,
                    movementType: 'return_out',
                    referenceType: 'purchase_return',
                    referenceId: $event->purchaseReturnId,
                    uomId: (int) $line['uom_id'],
                    quantity: (string) $line['return_qty'],
                    unitCost: isset($line['unit_cost']) ? (string) $line['unit_cost'] : null,
                    performedBy: null,
                    performedAt: new \DateTimeImmutable,
                    notes: 'Purchase return #'.$event->purchaseReturnId.' posted',
                    metadata: null,
                );

                $saved = $this->inventoryStockRepository->recordMovement($movement);
                $this->inventoryStockRepository->adjustStockLevel($saved);
                $this->traceLogRepository->recordForMovement($saved);
            }
        });
    }
}
