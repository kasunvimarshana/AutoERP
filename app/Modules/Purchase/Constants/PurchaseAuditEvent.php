<?php

declare(strict_types=1);

namespace Modules\Purchase\Constants;

final class PurchaseAuditEvent
{
    public const FAST_PURCHASE_COMPLETED = 'purchase.fast_purchase.completed';

    private function __construct() {}
}
