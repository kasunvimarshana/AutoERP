<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class PlatformOperatorInvitationDeliveryStatus
{
    public const QUEUED = 'queued';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::QUEUED, self::SENDING, self::SENT, self::FAILED, self::CANCELLED];
    }

    private function __construct() {}
}
