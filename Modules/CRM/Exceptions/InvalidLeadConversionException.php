<?php

declare(strict_types=1);

namespace Modules\CRM\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidLeadConversionException extends BusinessRuleException
{
    protected $code = 'INVALID_LEAD_CONVERSION';

    protected $message = 'Lead cannot be converted in current status';
}
