<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantOnboardingStatus
{
    public const PENDING = 'pending';
    public const PROVISIONING = 'provisioning';
    public const AWAITING_DOMAIN = 'awaiting_domain';
    public const READY = 'ready';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::PENDING,
            self::PROVISIONING,
            self::AWAITING_DOMAIN,
            self::READY,
            self::COMPLETED,
            self::FAILED,
        ];
    }

    private function __construct() {}
}
