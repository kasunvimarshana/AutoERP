<?php

namespace Modules\HR\Application\Listeners;

use Modules\HR\Application\UseCases\RecordLeaveAbsenceUseCase;
use Modules\Leave\Domain\Events\LeaveRequestApproved;


class HandleLeaveRequestApprovedListener
{
    public function __construct(
        private RecordLeaveAbsenceUseCase $recordAbsence,
    ) {}

    public function handle(LeaveRequestApproved $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if ($event->employeeId === '') {
            return;
        }

        if ($event->startDate === '' || $event->endDate === '') {
            return;
        }

        try {
            $this->recordAbsence->execute([
                'tenant_id'       => $event->tenantId,
                'employee_id'     => $event->employeeId,
                'start_date'      => $event->startDate,
                'end_date'        => $event->endDate,
                'leave_type_name' => $event->leaveTypeName,
                'notes'           => null,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: an absence record creation failure must never
            // prevent the leave request from being approved.
        }
    }
}
