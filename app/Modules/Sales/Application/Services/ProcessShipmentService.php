<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Domain\Events\ShipmentProcessed;
use Modules\Sales\Domain\Exceptions\ShipmentNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;

class ProcessShipmentService extends BaseService implements ProcessShipmentServiceInterface
{
    public function __construct(private readonly ShipmentRepositoryInterface $shipmentRepository)
    {
        parent::__construct($shipmentRepository);
    }

    protected function handle(array $data): Shipment
    {
        $id = (int) ($data['id'] ?? 0);
        $shipment = $this->shipmentRepository->find($id);

        if (! $shipment) {
            throw new ShipmentNotFoundException($id);
        }

        $shipment->process();
        $saved = $this->shipmentRepository->save($shipment);

        $this->addEvent(new ShipmentProcessed(
            tenantId: $saved->getTenantId(),
            shipmentId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            warehouseId: $saved->getWarehouseId(),
            lines: array_map(fn ($l) => [
                'id' => $l->getId(),
                'product_id' => $l->getProductId(),
                'from_location_id' => $l->getFromLocationId(),
                'uom_id' => $l->getUomId(),
                'shipped_qty' => $l->getShippedQty(),
                'unit_cost' => $l->getUnitCost(),
                'variant_id' => $l->getVariantId(),
                'batch_id' => $l->getBatchId(),
                'serial_id' => $l->getSerialId(),
            ], $saved->getLines()),
        ));

        return $saved;
    }
}
