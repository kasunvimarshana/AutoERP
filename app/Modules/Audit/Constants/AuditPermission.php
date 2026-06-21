<?php

declare(strict_types=1);

namespace Modules\Audit\Constants;

final class AuditPermission
{
    public const LOGS_VIEW = 'audit.logs.view';
    public const LOGS_VIEW_TENANT = 'audit.logs.view_tenant';
    public const LOGS_VIEW_SENSITIVE = 'audit.logs.view_sensitive';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::LOGS_VIEW => 'View audit-log summaries within the active organization scope.',
            self::LOGS_VIEW_TENANT => 'View audit logs across all organization units in the active tenant.',
            self::LOGS_VIEW_SENSITIVE => 'View sanitized audit change sets, metadata, and request context.',
        ];
    }

    private function __construct() {}
}
