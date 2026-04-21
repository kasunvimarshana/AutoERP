<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\FindShipmentServiceInterface;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;

class FindShipmentService extends BaseService implements FindShipmentServiceInterface
{
    public function __construct(private readonly ShipmentRepositoryInterface $shipmentRepository)
    {
        parent::__construct($shipmentRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
