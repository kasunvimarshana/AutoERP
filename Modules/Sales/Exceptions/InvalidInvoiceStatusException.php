<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidInvoiceStatusException extends BusinessRuleException
{
    protected $message = 'Invalid invoice status transition';

    protected $code = 'INVALID_INVOICE_STATUS';
}
