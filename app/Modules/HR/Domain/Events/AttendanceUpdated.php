<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Attendance;

class AttendanceUpdated extends BaseEvent
{
    public function __construct(public readonly Attendance $attendance)
    {
        parent::__construct($attendance->getTenantId());
    }
}
