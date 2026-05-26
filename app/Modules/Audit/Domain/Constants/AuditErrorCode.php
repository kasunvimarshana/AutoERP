<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Constants;

final class AuditErrorCode
{
    public const INVALID_VALUE = 'AUDIT_INVALID_VALUE';

    public const NOT_FOUND = 'AUDIT_NOT_FOUND';

    public const LOG_WRITE_FAILED = 'AUDIT_LOG_WRITE_FAILED';

    public const EVENT_CAPTURE_FAILED = 'AUDIT_EVENT_CAPTURE_FAILED';

    private function __construct()
    {
    }
}