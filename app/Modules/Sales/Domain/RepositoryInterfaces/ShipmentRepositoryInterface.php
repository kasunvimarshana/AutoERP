<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Sales\Domain\Entities\Shipment;

interface ShipmentRepositoryInterface extends RepositoryInterface
{
    public function save(Shipment $shipment): Shipment;

    public function findByTenantAndShipmentNumber(int $tenantId, string $shipmentNumber): ?Shipment;

    public function find(int|string $id, array $columns = ['*']): ?Shipment;
}
