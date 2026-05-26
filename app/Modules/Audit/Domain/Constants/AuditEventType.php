<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Constants;

final class AuditEventType
{
    public const ACTIVITY = 'activity';

    public const ENTITY_CHANGE = 'entity_change';

    public const SYSTEM_EVENT = 'system_event';

    public const SECURITY = 'security';

    private function __construct()
    {
    }
}
