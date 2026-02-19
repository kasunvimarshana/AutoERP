<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class AccountNotFoundException extends NotFoundException
{
    protected $message = 'Account not found';

    protected $errorCode = 'ACCOUNT_NOT_FOUND';
}
