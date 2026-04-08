<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class InventoryValuationLayerNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('InventoryValuationLayer', $id);
    }
}
