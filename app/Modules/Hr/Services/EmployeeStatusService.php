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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EmployeeStatusService
{
    public function recordInitial(HrEmployee $employee, ?int $changedBy = null): void
    {
        $this->history($employee, null, $employee->status, null, $changedBy);
    }

    public function change(HrEmployee $employee, EmployeeStatusChangeData $data): HrEmployee
    {
        return DB::transaction(function () use ($employee, $data): HrEmployee {
            $employee = HrEmployee::query()
                ->whereKey($employee->getKey())
                ->where('tenant_id', (int) $employee->tenant_id)
                ->when(
                    $employee->organization_unit_id === null,
                    static fn ($query) => $query->whereNull('organization_unit_id'),
                    static fn ($query) => $query->where('organization_unit_id', (int) $employee->organization_unit_id),
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($data->rowVersion !== (int) $employee->row_version) {
                throw new ConflictHttpException('Employee was changed by someone else. Reload before changing status.');
            }

            $from = $employee->status;
            $this->assertTransition($from, $data->newStatus);
            if ($from === $data->newStatus) {
                return $employee;
            }

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
            $employee->setAttribute('row_version', ((int) $employee->row_version) + 1);
            $employee->save();

            return $employee->refresh();
        });
    }

    public function changeTo(HrEmployee $employee, EmployeeStatus $status, int $rowVersion, ?int $changedBy = null, ?string $reason = null): HrEmployee
    {
        return $this->change($employee, new EmployeeStatusChangeData($status, $rowVersion, $reason, $changedBy));
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
