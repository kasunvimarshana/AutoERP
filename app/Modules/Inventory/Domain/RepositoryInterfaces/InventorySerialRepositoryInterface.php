<?php
namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventorySerial;

interface InventorySerialRepositoryInterface
{
    public function findById(int $id): ?InventorySerial;
    public function findBySerial(int $tenantId, string $serialNumber): ?InventorySerial;
    public function create(array $data): InventorySerial;
    public function update(InventorySerial $serial, array $data): InventorySerial;
}
