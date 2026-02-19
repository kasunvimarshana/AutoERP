<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidPaymentAmountException extends BusinessRuleException
{
    protected $message = 'Invalid payment amount';

    protected $code = 'INVALID_PAYMENT_AMOUNT';
}
