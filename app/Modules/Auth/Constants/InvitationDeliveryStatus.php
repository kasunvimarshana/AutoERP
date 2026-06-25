<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class InvitationDeliveryStatus
{
    public const QUEUED = 'queued';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const DELIVERED = 'delivered';
    public const BOUNCED = 'bounced';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::QUEUED,
            self::SENDING,
            self::SENT,
            self::DELIVERED,
            self::BOUNCED,
            self::FAILED,
            self::CANCELLED,
        ];
    }

    /** @return list<string> */
    public static function claimable(): array
    {
        return [self::QUEUED, self::FAILED, self::SENDING];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, [self::SENT, self::DELIVERED, self::BOUNCED, self::CANCELLED], true);
    }

    private function __construct() {}
}
