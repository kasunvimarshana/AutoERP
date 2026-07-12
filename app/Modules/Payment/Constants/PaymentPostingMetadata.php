<?php

declare(strict_types=1);

namespace Modules\Payment\Constants;

final class PaymentPostingMetadata
{
    public const PROFILE_CODE = 'posting_profile_code';
    public const COUNTERPARTY_ROLE = 'counterparty_profile_key';

    private function __construct() {}
}
