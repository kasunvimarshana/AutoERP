<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class StockCountNotFoundException extends NotFoundException
{
    protected $message = 'Stock count not found';

    protected $code = 'STOCK_COUNT_NOT_FOUND';
}
