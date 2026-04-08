<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class InventoryItemNotFoundException extends NotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('InventoryItem', $id);
    }
}
