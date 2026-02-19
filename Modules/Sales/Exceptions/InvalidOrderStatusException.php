<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidOrderStatusException extends BusinessRuleException
{
    protected $message = 'Invalid order status transition';

    protected $code = 'INVALID_ORDER_STATUS';
}
