<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class InvitationDeliveryStatus
{
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const NOT_REQUIRED = 'not_required';

    private function __construct() {}
}
