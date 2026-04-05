<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\DTOs\ReceiveStockData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockReceived;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class ReceiveStockService implements ReceiveStockServiceInterface
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $levelRepo,
        private readonly InventoryBatchRepositoryInterface $batchRepo,
        private readonly InventoryValuationLayerRepositoryInterface $layerRepo,
    ) {}

    public function execute(ReceiveStockData $data): InventoryLevel
    {
        return DB::transaction(function () use ($data): InventoryLevel {
            $level = $this->levelRepo->upsert(
                $data->tenant_id, $data->product_id, $data->warehouse_id,
                $data->location_id, $data->valuation_method
            );

            $level->receive($data->quantity);
            $this->levelRepo->update($level->getId(), [
                'quantity_on_hand' => $level->getQuantityOnHand(),
            ]);

            if ($data->batch_number) {
                $this->batchRepo->create([
                    'tenant_id'       => $data->tenant_id,
                    'product_id'      => $data->product_id,
                    'warehouse_id'    => $data->warehouse_id,
                    'batch_number'    => $data->batch_number,
                    'lot_number'      => $data->lot_number,
                    'serial_number'   => $data->serial_number,
                    'quantity'        => $data->quantity,
                    'quantity_remaining' => $data->quantity,
                    'cost_price'      => $data->unit_cost,
                    'manufactured_at' => $data->manufactured_at,
                    'expires_at'      => $data->expires_at,
                    'received_at'     => now(),
                    'status'          => 'active',
                    'reference'       => $data->reference,
                ]);
            }

            $this->layerRepo->create([
                'tenant_id'          => $data->tenant_id,
                'product_id'         => $data->product_id,
                'warehouse_id'       => $data->warehouse_id,
                'quantity'           => $data->quantity,
                'quantity_remaining' => $data->quantity,
                'unit_cost'          => $data->unit_cost,
                'received_at'        => now(),
                'reference'          => $data->reference,
            ]);

            event(new StockReceived($data->tenant_id, $data->product_id, $data->warehouse_id, $data->quantity));

            return $level;
        });
    }
}
