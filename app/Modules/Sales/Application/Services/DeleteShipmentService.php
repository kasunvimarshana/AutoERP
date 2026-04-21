<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\DeleteShipmentServiceInterface;
use Modules\Sales\Domain\Exceptions\ShipmentNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;

class DeleteShipmentService extends BaseService implements DeleteShipmentServiceInterface
{
    public function __construct(private readonly ShipmentRepositoryInterface $shipmentRepository)
    {
        parent::__construct($shipmentRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $shipment = $this->shipmentRepository->find($id);

        if (! $shipment) {
            throw new ShipmentNotFoundException($id);
        }

        if ($shipment->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Only draft shipments can be deleted.');
        }

        return $this->shipmentRepository->delete($id);
    }
}
