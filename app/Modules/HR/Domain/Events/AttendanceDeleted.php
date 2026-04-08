<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AttendanceDeleted extends BaseEvent
{
    public function __construct(int $tenantId, public readonly mixed $attendanceId = null)
    {
        parent::__construct($tenantId);
    }
}
