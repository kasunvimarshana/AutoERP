<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Domain\Entities\Shipment;
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

        return $this->shipmentRepository->save($shipment);
    }
}
