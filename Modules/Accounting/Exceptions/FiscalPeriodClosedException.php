<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\BusinessLogicException;

class FiscalPeriodClosedException extends BusinessLogicException
{
    protected $message = 'Cannot post to a closed fiscal period';

    protected $errorCode = 'FISCAL_PERIOD_CLOSED';
}
