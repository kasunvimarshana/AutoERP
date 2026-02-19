<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class StockItemNotFoundException extends NotFoundException
{
    protected $message = 'Stock item not found';

    protected $code = 'STOCK_ITEM_NOT_FOUND';
}
