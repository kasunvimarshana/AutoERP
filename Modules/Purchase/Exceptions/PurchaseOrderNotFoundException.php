<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class PurchaseOrderNotFoundException extends NotFoundException
{
    protected $message = 'Purchase order not found';

    protected $code = 'PURCHASE_ORDER_NOT_FOUND';
}
