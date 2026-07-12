<?php

declare(strict_types=1);

namespace Modules\Payment\Constants;

final class PaymentAllocationFinanceMetadata
{
    public const POSTING_REFERENCE = 'finance_posting_reference';
    public const REVERSAL_REFERENCE = 'finance_reversal_reference';

    private function __construct() {}
}
