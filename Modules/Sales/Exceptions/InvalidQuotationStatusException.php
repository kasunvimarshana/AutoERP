<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidQuotationStatusException extends BusinessRuleException
{
    protected $message = 'Invalid quotation status transition';

    protected $code = 'INVALID_QUOTATION_STATUS';
}
