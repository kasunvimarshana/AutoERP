<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class AttendanceCheckedOut extends DomainEvent
{
    public function __construct(
        public readonly string $attendanceId,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly string $checkOut,
        public readonly string $durationHours,
    ) {
        parent::__construct();
    }
}
