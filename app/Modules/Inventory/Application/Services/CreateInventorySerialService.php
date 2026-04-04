<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\CreateInventorySerialServiceInterface;
use Modules\Inventory\Domain\Entities\InventorySerial;
use Modules\Inventory\Domain\Events\InventorySerialCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialRepositoryInterface;

class CreateInventorySerialService implements CreateInventorySerialServiceInterface
{
    public function __construct(private readonly InventorySerialRepositoryInterface $repository) {}

    public function execute(array $data): InventorySerial
    {
        $serial = $this->repository->create($data);

        Event::dispatch(new InventorySerialCreated($serial->tenantId, $serial->id));

        return $serial;
    }
}
