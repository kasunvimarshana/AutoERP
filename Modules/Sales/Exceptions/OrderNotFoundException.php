<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class OrderNotFoundException extends NotFoundException
{
    protected $message = 'Order not found';

    protected $code = 'ORDER_NOT_FOUND';
}
