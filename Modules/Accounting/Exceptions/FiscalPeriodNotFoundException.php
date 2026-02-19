<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class FiscalPeriodNotFoundException extends NotFoundException
{
    protected $message = 'Fiscal period not found';

    protected $errorCode = 'FISCAL_PERIOD_NOT_FOUND';
}
