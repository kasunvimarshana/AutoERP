<?php

declare(strict_types=1);

namespace Modules\Finance\Constants;

final class FinanceSystemAccountCode
{
    public const CUSTOMER_SECURITY_DEPOSIT = 'CUSTOMER-SECURITY-DEPOSIT';

    public const REALIZED_FX_GAIN = 'FX-REALIZED-GAIN';

    public const REALIZED_FX_LOSS = 'FX-REALIZED-LOSS';

    private function __construct() {}
}
