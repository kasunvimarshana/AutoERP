<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class WarehouseNotFoundException extends NotFoundException
{
    protected $message = 'Warehouse not found';

    protected $code = 'WAREHOUSE_NOT_FOUND';
}
