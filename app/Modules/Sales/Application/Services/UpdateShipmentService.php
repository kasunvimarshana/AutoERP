<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\UpdateShipmentServiceInterface;
use Modules\Sales\Application\DTOs\ShipmentData;
use Modules\Sales\Application\DTOs\ShipmentLineData;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Domain\Entities\ShipmentLine;
use Modules\Sales\Domain\Exceptions\ShipmentNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;

class UpdateShipmentService extends BaseService implements UpdateShipmentServiceInterface
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

        $dto = ShipmentData::fromArray($data);

        if ($shipment->getTenantId() !== $dto->tenantId) {
            throw new ShipmentNotFoundException($id);
        }

        $shippedDate = $dto->shippedDate !== null
            ? new \DateTimeImmutable($dto->shippedDate)
            : $shipment->getShippedDate();

        $shipment->update(
            customerId: $dto->customerId,
            warehouseId: $dto->warehouseId,
            currencyId: $dto->currencyId,
            salesOrderId: $dto->salesOrderId,
            shipmentNumber: $dto->shipmentNumber,
            shippedDate: $shippedDate,
            carrier: $dto->carrier,
            trackingNumber: $dto->trackingNumber,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): ShipmentLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $shipment->setLines($lines);
        }

        return $this->shipmentRepository->save($shipment);
    }

    private static function buildLine(int $tenantId, array $lineData): ShipmentLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineDto = ShipmentLineData::fromArray($lineData);

        return new ShipmentLine(
            tenantId: $lineDto->tenantId,
            productId: $lineDto->productId,
            fromLocationId: $lineDto->fromLocationId,
            uomId: $lineDto->uomId,
            shipmentId: $lineDto->shipmentId,
            salesOrderLineId: $lineDto->salesOrderLineId,
            variantId: $lineDto->variantId,
            batchId: $lineDto->batchId,
            serialId: $lineDto->serialId,
            shippedQty: $lineDto->shippedQty,
            unitCost: $lineDto->unitCost,
            id: $lineDto->id,
        );
    }
}
