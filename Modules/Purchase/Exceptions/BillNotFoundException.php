<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class BillNotFoundException extends NotFoundException
{
    protected $message = 'Bill not found';

    protected $code = 'BILL_NOT_FOUND';
}
