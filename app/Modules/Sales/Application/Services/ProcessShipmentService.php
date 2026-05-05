<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Domain\Events\ShipmentProcessed;
use Modules\Sales\Domain\Exceptions\ShipmentNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;

class ProcessShipmentService extends BaseService implements ProcessShipmentServiceInterface
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipmentRepository,
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
    ) {
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

        $this->updateSalesOrderStatus($saved);

        $this->addEvent(new ShipmentProcessed(
            tenantId: $saved->getTenantId(),
            shipmentId: (int) $saved->getId(),
            customerId: $saved->getCustomerId(),
            warehouseId: $saved->getWarehouseId(),
            salesOrderId: $saved->getSalesOrderId(),
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

    private function updateSalesOrderStatus(Shipment $shipment): void
    {
        $salesOrderId = $shipment->getSalesOrderId();
        if ($salesOrderId === null) {
            return;
        }

        $so = $this->salesOrderRepository->find($salesOrderId);
        if ($so === null || ! in_array($so->getStatus(), ['confirmed', 'partial'], true)) {
            return;
        }

        // Build a map of salesOrderLineId → shipped qty from this shipment.
        $shippedByLineId = [];
        foreach ($shipment->getLines() as $shipLine) {
            $soLineId = $shipLine->getSalesOrderLineId();
            if ($soLineId === null) {
                continue;
            }
            $shippedByLineId[$soLineId] = bcadd(
                $shippedByLineId[$soLineId] ?? '0.000000',
                $shipLine->getShippedQty(),
                6,
            );
        }

        if ($shippedByLineId === []) {
            return;
        }

        // Update each SO line's shipped_qty.
        $soLines = $so->getLines();
        foreach ($soLines as $soLine) {
            $soLineId = $soLine->getId();
            if ($soLineId !== null && isset($shippedByLineId[$soLineId])) {
                $soLine->addShippedQty($shippedByLineId[$soLineId]);
            }
        }
        $so->setLines($soLines);

        // Determine new SO status from the updated line totals.
        $fullyShipped = true;
        $anyShipped   = false;
        foreach ($so->getLines() as $soLine) {
            if (bccomp($soLine->getShippedQty(), '0.000000', 6) > 0) {
                $anyShipped = true;
            }
            if (bccomp($soLine->getShippedQty(), $soLine->getOrderedQty(), 6) < 0) {
                $fullyShipped = false;
            }
        }

        DB::transaction(function () use ($so, $fullyShipped, $anyShipped): void {
            if ($fullyShipped && $anyShipped) {
                $so->markShipped();
            } elseif ($anyShipped && $so->getStatus() === 'confirmed') {
                $so->markPartial();
            }
            $this->salesOrderRepository->save($so);
        });
    }
}
