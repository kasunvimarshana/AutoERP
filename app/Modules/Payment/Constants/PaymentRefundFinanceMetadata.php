<?php

declare(strict_types=1);

namespace Modules\Payment\Constants;

final class PaymentRefundFinanceMetadata
{
    public const FX_POSTING_REFERENCE = 'refund_fx_posting_reference';

    public const FX_REVERSAL_REFERENCE = 'refund_fx_reversal_reference';

    private function __construct() {}
}
