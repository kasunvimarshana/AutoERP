<?php

declare(strict_types=1);

namespace Modules\CRM\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class CustomerNotFoundException extends NotFoundException
{
    protected $code = 'CUSTOMER_NOT_FOUND';

    protected $message = 'Customer not found';
}
