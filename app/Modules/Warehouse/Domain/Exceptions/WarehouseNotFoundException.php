<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class WarehouseNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Warehouse [{$id}] not found.");
    }
}
