<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeStatusChangeData;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeStatusHistory;

final class EmployeeStatusService
{
    public function recordInitial(HrEmployee $employee, ?int $changedBy = null): void
    {
        $this->history($employee, null, $employee->status, null, $changedBy);
    }

    public function change(HrEmployee $employee, EmployeeStatusChangeData $data): HrEmployee
    {
        $from = $employee->status;
        $this->assertTransition($from, $data->newStatus);
        if ($from === $data->newStatus) {
            return $employee;
        }
        return DB::transaction(function () use ($employee, $data, $from): HrEmployee {
            $this->history($employee, $from, $data->newStatus, $data->reason, $data->changedBy);
            $employee->status = $data->newStatus;
            $employee->availability_status = match ($data->newStatus) {
                EmployeeStatus::OnLeave => EmployeeAvailabilityStatus::OnLeave,
                EmployeeStatus::Suspended => EmployeeAvailabilityStatus::Suspended,
                EmployeeStatus::Inactive, EmployeeStatus::Terminated => EmployeeAvailabilityStatus::Inactive,
                default => $employee->availability_status,
            };
            if ($data->newStatus === EmployeeStatus::Active && $employee->approved_at === null) {
                $employee->approved_by = $data->changedBy;
                $employee->approved_at = now();
            }
            $employee->save();
            return $employee->refresh();
        });
    }

    public function changeTo(HrEmployee $employee, EmployeeStatus $status, ?int $changedBy = null, ?string $reason = null): HrEmployee
    {
        return $this->change($employee, new EmployeeStatusChangeData($status, $reason, $changedBy));
    }

    public function assertTransition(EmployeeStatus $from, EmployeeStatus $to): void
    {
        if ($from === $to) {
            return;
        }
        $allowed = [
            EmployeeStatus::PendingApproval->value => [EmployeeStatus::Active, EmployeeStatus::Inactive],
            EmployeeStatus::Active->value => [EmployeeStatus::Inactive, EmployeeStatus::OnLeave, EmployeeStatus::Suspended, EmployeeStatus::Terminated],
            EmployeeStatus::Inactive->value => [EmployeeStatus::Active, EmployeeStatus::Terminated],
            EmployeeStatus::OnLeave->value => [EmployeeStatus::Active, EmployeeStatus::Inactive, EmployeeStatus::Terminated],
            EmployeeStatus::Suspended->value => [EmployeeStatus::Active, EmployeeStatus::Inactive, EmployeeStatus::Terminated],
            EmployeeStatus::Terminated->value => [],
        ];
        if (! in_array($to, $allowed[$from->value] ?? [], true)) {
            throw new InvalidArgumentException('Invalid employee status transition.');
        }
    }

    private function history(HrEmployee $employee, ?EmployeeStatus $old, EmployeeStatus $new, ?string $reason, ?int $changedBy): void
    {
        HrEmployeeStatusHistory::query()->create(['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id, 'employee_id' => $employee->getKey(), 'old_status' => $old, 'new_status' => $new, 'reason' => $reason, 'changed_by' => $changedBy, 'changed_at' => now()]);
    }
}
