<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class InventoryLevelNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('InventoryLevel', $id);
    }
}
