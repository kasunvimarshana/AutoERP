<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class WarehouseZoneNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('WarehouseZone', $id);
    }
}
