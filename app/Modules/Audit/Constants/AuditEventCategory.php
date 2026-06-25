<?php

declare(strict_types=1);

namespace Modules\Audit\Constants;

final class AuditEventCategory
{
    public const AUTHENTICATION = 'authentication';
    public const AUTHORIZATION = 'authorization';
    public const CONFIGURATION = 'configuration';
    public const DATA = 'data';
    public const FINANCIAL = 'financial';
    public const INVENTORY = 'inventory';
    public const WORKFLOW = 'workflow';
    public const SYSTEM = 'system';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::AUTHENTICATION,
            self::AUTHORIZATION,
            self::CONFIGURATION,
            self::DATA,
            self::FINANCIAL,
            self::INVENTORY,
            self::WORKFLOW,
            self::SYSTEM,
        ];
    }

    private function __construct() {}
}
