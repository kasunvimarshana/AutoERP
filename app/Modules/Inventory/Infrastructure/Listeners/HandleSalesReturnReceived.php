<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Sales\Domain\Events\SalesReturnReceived;

class HandleSalesReturnReceived
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $inventoryStockRepository,
        private readonly TraceLogRepositoryInterface $traceLogRepository,
    ) {}

    public function handle(SalesReturnReceived $event): void
    {
        if (empty($event->lines)) {
            return;
        }

        DB::transaction(function () use ($event): void {
            foreach ($event->lines as $line) {
                $toLocationId = $line['to_location_id'] ?? null;
                if ($toLocationId === null) {
                    Log::warning('HandleSalesReturnReceived: missing to_location_id for return line', [
                        'sales_return_id' => $event->salesReturnId,
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
                    toLocationId: (int) $toLocationId,
                    movementType: 'return_in',
                    referenceType: 'sales_return',
                    referenceId: $event->salesReturnId,
                    uomId: (int) $line['uom_id'],
                    quantity: (string) $line['return_qty'],
                    unitCost: null,
                    performedBy: null,
                    performedAt: new \DateTimeImmutable,
                    notes: 'Sales return #'.$event->salesReturnId.' received',
                    metadata: null,
                );

                $saved = $this->inventoryStockRepository->recordMovement($movement);
                $this->inventoryStockRepository->adjustStockLevel($saved);
                $this->traceLogRepository->recordForMovement($saved);
            }
        });
    }
}
