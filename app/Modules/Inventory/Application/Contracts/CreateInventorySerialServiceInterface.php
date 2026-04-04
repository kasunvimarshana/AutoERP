<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventorySerial;

interface CreateInventorySerialServiceInterface
{
    public function execute(array $data): InventorySerial;
}
