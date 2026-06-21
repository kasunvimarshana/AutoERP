<?php

declare(strict_types=1);

namespace Modules\Audit\Constants;

final class AuditActorType
{
    public const USER = 'user';
    public const SYSTEM = 'system';
    public const INTEGRATION = 'integration';
    public const JOB = 'job';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::USER, self::SYSTEM, self::INTEGRATION, self::JOB];
    }

    private function __construct() {}
}
