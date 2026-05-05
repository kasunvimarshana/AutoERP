<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Domain\Entities\ShipmentLine;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\ShipmentModel;

class EloquentShipmentRepository extends EloquentRepository implements ShipmentRepositoryInterface
{
    public function __construct(ShipmentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ShipmentModel $m): Shipment => $this->mapModelToDomainEntity($m));
    }

    public function save(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment): Shipment {
            $data = [
                'tenant_id' => $shipment->getTenantId(),
                'customer_id' => $shipment->getCustomerId(),
                'sales_order_id' => $shipment->getSalesOrderId(),
                'warehouse_id' => $shipment->getWarehouseId(),
                'shipment_number' => $shipment->getShipmentNumber(),
                'status' => $shipment->getStatus(),
                'shipped_date' => $shipment->getShippedDate()?->format('Y-m-d'),
                'carrier' => $shipment->getCarrier(),
                'tracking_number' => $shipment->getTrackingNumber(),
                'currency_id' => $shipment->getCurrencyId(),
                'notes' => $shipment->getNotes(),
                'metadata' => $shipment->getMetadata(),
            ];

            if ($shipment->getId()) {
                $model = $this->update($shipment->getId(), $data);
            } else {
                $model = $this->create($data);
            }

            /** @var ShipmentModel $model */
            $keptLineIds = [];
            foreach ($shipment->getLines() as $line) {
                $lineData = [
                    'tenant_id' => (int) $model->tenant_id,
                    'sales_order_line_id' => $line->getSalesOrderLineId(),
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'batch_id' => $line->getBatchId(),
                    'serial_id' => $line->getSerialId(),
                    'from_location_id' => $line->getFromLocationId(),
                    'uom_id' => $line->getUomId(),
                    'shipped_qty' => $line->getShippedQty(),
                    'unit_cost' => $line->getUnitCost(),
                ];

                $lineId = $line->getId();
                if ($lineId !== null) {
                    $updated = $model->lines()
                        ->where('tenant_id', (int) $model->tenant_id)
                        ->whereKey($lineId)
                        ->update($lineData);

                    if ($updated > 0) {
                        $keptLineIds[] = $lineId;
                        continue;
                    }
                }

                $createdLine = $model->lines()->create($lineData);
                $keptLineIds[] = (int) $createdLine->id;
            }

            $lineCleanupQuery = $model->lines()->where('tenant_id', (int) $model->tenant_id);
            if ($keptLineIds === []) {
                $lineCleanupQuery->delete();
            } else {
                $lineCleanupQuery->whereNotIn('id', $keptLineIds)->delete();
            }

            $model->load('lines');

            return $this->toDomainEntity($model);
        });
    }

    public function find(int|string $id, array $columns = ['*']): ?Shipment
    {
        /** @var ShipmentModel|null $model */
        $model = $this->model->newQuery()->with('lines')->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndShipmentNumber(int $tenantId, string $shipmentNumber): ?Shipment
    {
        /** @var ShipmentModel|null $model */
        $model = $this->model->newQuery()->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('shipment_number', $shipmentNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(ShipmentModel $model): Shipment
    {
        $shipment = new Shipment(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            warehouseId: (int) $model->warehouse_id,
            currencyId: (int) $model->currency_id,
            salesOrderId: $model->sales_order_id !== null ? (int) $model->sales_order_id : null,
            shipmentNumber: $model->shipment_number,
            status: (string) $model->status,
            shippedDate: $model->shipped_date !== null
                ? new \DateTimeImmutable((string) $model->shipped_date)
                : null,
            carrier: $model->carrier,
            trackingNumber: $model->tracking_number,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        $lines = [];
        foreach ($model->lines ?? [] as $lineModel) {
            $lines[] = new ShipmentLine(
                tenantId: (int) $lineModel->tenant_id,
                productId: (int) $lineModel->product_id,
                fromLocationId: (int) $lineModel->from_location_id,
                uomId: (int) $lineModel->uom_id,
                shipmentId: (int) $lineModel->shipment_id,
                salesOrderLineId: $lineModel->sales_order_line_id !== null ? (int) $lineModel->sales_order_line_id : null,
                variantId: $lineModel->variant_id !== null ? (int) $lineModel->variant_id : null,
                batchId: $lineModel->batch_id !== null ? (int) $lineModel->batch_id : null,
                serialId: $lineModel->serial_id !== null ? (int) $lineModel->serial_id : null,
                shippedQty: (string) $lineModel->shipped_qty,
                unitCost: $lineModel->unit_cost !== null ? (string) $lineModel->unit_cost : null,
                id: (int) $lineModel->id,
            );
        }
        $shipment->setLines($lines);

        return $shipment;
    }
}
