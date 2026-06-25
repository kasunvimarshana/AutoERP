<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantOnboardingStepStatus
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::RUNNING, self::COMPLETED, self::FAILED];
    }

    private function __construct() {}
}
