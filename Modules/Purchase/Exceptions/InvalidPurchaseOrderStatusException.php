<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\BusinessException;

class InvalidPurchaseOrderStatusException extends BusinessException
{
    protected $message = 'Invalid purchase order status for this operation';

    protected $code = 'INVALID_PURCHASE_ORDER_STATUS';
}
