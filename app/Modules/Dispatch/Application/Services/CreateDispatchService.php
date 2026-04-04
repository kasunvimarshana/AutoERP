<?php

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchCreated;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Domain\ValueObjects\DispatchStatus;

class CreateDispatchService implements CreateDispatchServiceInterface
{
    public function __construct(
        private readonly DispatchRepositoryInterface $dispatchRepository,
        private readonly DispatchLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(DispatchData $data): Dispatch
    {
        $dispatch = $this->dispatchRepository->create([
            'tenant_id'        => $data->tenantId,
            'sales_order_id'   => $data->salesOrderId,
            'warehouse_id'     => $data->warehouseId,
            'dispatch_number'  => $data->dispatchNumber,
            'status'           => DispatchStatus::PENDING,
            'carrier'          => $data->carrier,
            'tracking_number'  => $data->trackingNumber,
            'shipping_address' => $data->shippingAddress,
        ]);

        foreach ($data->lines as $line) {
            $this->lineRepository->create([
                'dispatch_id'          => $dispatch->id,
                'sales_order_line_id'  => $line['sales_order_line_id'],
                'product_id'           => $line['product_id'],
                'variant_id'           => $line['variant_id'] ?? null,
                'dispatched_qty'       => $line['dispatched_qty'],
                'location_id'          => $line['location_id'],
                'batch_id'             => $line['batch_id'] ?? null,
                'serial_number'        => $line['serial_number'] ?? null,
                'lot_number'           => $line['lot_number'] ?? null,
            ]);
        }

        Event::dispatch(new DispatchCreated($dispatch->tenantId, $dispatch->id));

        return $dispatch;
    }
}
