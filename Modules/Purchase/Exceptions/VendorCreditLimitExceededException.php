<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class VendorCreditLimitExceededException extends BusinessRuleException
{
    public function __construct(string $vendorName, string $creditLimit, string $currentBalance)
    {
        parent::__construct(
            "Vendor {$vendorName} has exceeded credit limit. Limit: {$creditLimit}, Current balance: {$currentBalance}",
            'VENDOR_CREDIT_LIMIT_EXCEEDED',
            422
        );
    }
}
