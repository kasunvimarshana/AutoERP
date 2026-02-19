<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class StockMovementNotFoundException extends NotFoundException
{
    protected $message = 'Stock movement not found';

    protected $code = 'STOCK_MOVEMENT_NOT_FOUND';
}
