<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use InvalidArgumentException;
use Modules\Hr\DTOs\CompOffClaimData;
use Modules\Hr\DTOs\LeavePolicyData;
use Modules\Hr\DTOs\LeaveRequestData;
use Modules\Hr\DTOs\LeaveTypeData;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrCompOffClaim;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Models\HrLeavePolicy;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;

final class LeaveManagementService
{
    public function createLeaveType(int $tenantId, LeaveTypeData $data): HrLeaveType
    {
        return HrLeaveType::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'code' => $data->code,
            'name' => $data->name,
            'color' => $data->color,
            'is_paid' => $data->isPaid,
            'requires_attachment' => $data->requiresAttachment,
            'attachment_mandatory_after_days' => $data->attachmentMandatoryAfterDays,
            'allow_half_day' => $data->allowHalfDay,
            'allow_hourly' => $data->allowHourly,
            'is_compensatory' => $data->isCompensatory,
            'sandwich_rule_applied' => $data->sandwichRuleApplied,
            'status' => $data->status,
        ]);
    }

    public function createLeavePolicy(int $tenantId, LeavePolicyData $data): HrLeavePolicy
    {
        return HrLeavePolicy::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'leave_type_id' => $data->leaveTypeId,
            'department_id' => $data->departmentId,
            'employment_type_id' => $data->employmentTypeId,
            'name' => $data->name,
            'annual_allocation_days' => $data->annualAllocationDays,
            'accrual_frequency' => $data->accrualFrequency,
            'max_carry_forward_days' => $data->maxCarryForwardDays,
            'carry_forward_expiry_months' => $data->carryForwardExpiryMonths,
            'max_consecutive_days' => $data->maxConsecutiveDays,
            'probation_restricted' => $data->probationRestricted,
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
        ]);
    }

    public function getOrCreateBalance(int $tenantId, int $employeeId, int $leaveTypeId, int $calendarYear): HrLeaveBalance
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $balance = HrLeaveBalance::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'calendar_year' => $calendarYear,
            ],
            [
                'organization_unit_id' => $employee->organization_unit_id,
                'opening_balance' => '0.00',
                'accrued_days' => '0.00',
                'carried_forward_days' => '0.00',
                'used_days' => '0.00',
                'pending_approval_days' => '0.00',
                'expired_days' => '0.00',
                'current_balance' => '0.00',
            ],
        );

        return $balance;
    }

    public function applyLeave(int $tenantId, LeaveRequestData $data): HrLeaveRequest
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->employeeId)
            ->firstOrFail();

        $leaveType = HrLeaveType::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->leaveTypeId)
            ->firstOrFail();

        $year = CarbonImmutable::parse($data->startDate)->year;
        $balance = $this->getOrCreateBalance($tenantId, $data->employeeId, $data->leaveTypeId, $year);

        $requestedDays = (float) $data->totalDays;
        $availableDays = (float) $balance->current_balance - (float) $balance->pending_approval_days;

        if ($leaveType->is_paid && ! $leaveType->is_compensatory && $availableDays < $requestedDays) {
            throw new InvalidArgumentException("Insufficient leave balance. Available: {$availableDays}, Requested: {$requestedDays}");
        }

        // Atomically update balance pending days
        $balance->update([
            'row_version' => $balance->row_version + 1,
            'pending_approval_days' => bcadd((string) $balance->pending_approval_days, (string) $requestedDays, 2),
        ]);

        return HrLeaveRequest::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
            'employee_id' => $data->employeeId,
            'leave_type_id' => $data->leaveTypeId,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'duration_type' => $data->durationType,
            'total_days' => $data->totalDays,
            'reason' => $data->reason,
            'attachment_document_id' => $data->attachmentDocumentId,
            'status' => LeaveRequestStatus::Pending,
        ]);
    }

    public function actionLeaveRequest(
        HrLeaveRequest $request,
        int $approverUserId,
        bool $approve,
        ?string $notes = null,
    ): HrLeaveRequest {
        if ($request->status !== LeaveRequestStatus::Pending) {
            throw new InvalidArgumentException('Only pending leave requests can be actioned.');
        }

        $tenantId = (int) $request->tenant_id;
        $employeeId = (int) $request->employee_id;
        $year = CarbonImmutable::parse($request->start_date)->year;
        $balance = $this->getOrCreateBalance($tenantId, $employeeId, (int) $request->leave_type_id, $year);

        $days = (string) $request->total_days;

        if ($approve) {
            $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
            $newUsed = bcadd((string) $balance->used_days, $days, 2);
            $newCurrent = bcsub((string) $balance->current_balance, $days, 2);

            $balance->update([
                'row_version' => $balance->row_version + 1,
                'pending_approval_days' => max('0.00', $newPending),
                'used_days' => $newUsed,
                'current_balance' => $newCurrent,
            ]);

            // Mark attendance records as OnLeave for the covered dates
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                $civilDate = $date->toDateString();
                $record = HrAttendanceRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $civilDate)
                    ->first();

                if ($record === null) {
                    $record = HrAttendanceRecord::create([
                        'tenant_id' => $tenantId,
                        'employee_id' => $employeeId,
                        'attendance_date' => $civilDate,
                        'organization_unit_id' => $request->organization_unit_id,
                        'status' => AttendanceStatus::OnLeave,
                        'lock_status' => AttendanceLockStatus::Open,
                    ]);
                }

                if ($record->lock_status !== AttendanceLockStatus::Finalized) {
                    $record->update([
                        'status' => AttendanceStatus::OnLeave,
                        'notes' => 'On Leave: approved leave request #'.$request->getKey(),
                    ]);
                }
            }
        } else {
            // Revert pending approval days
            $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
            $balance->update([
                'row_version' => $balance->row_version + 1,
                'pending_approval_days' => max('0.00', $newPending),
            ]);
        }

        $request->update([
            'row_version' => $request->row_version + 1,
            'status' => $approve ? LeaveRequestStatus::Approved : LeaveRequestStatus::Rejected,
            'approver_id' => $approverUserId,
            'approved_at' => now(),
            'action_notes' => $notes,
        ]);

        return $request->refresh();
    }

    public function cancelLeaveRequest(HrLeaveRequest $request, int $cancellerUserId, string $reason): HrLeaveRequest
    {
        if (in_array($request->status, [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled], true)) {
            throw new InvalidArgumentException('Cannot cancel a leave request that is already rejected or cancelled.');
        }

        $tenantId = (int) $request->tenant_id;
        $employeeId = (int) $request->employee_id;
        $year = CarbonImmutable::parse($request->start_date)->year;
        $balance = $this->getOrCreateBalance($tenantId, $employeeId, (int) $request->leave_type_id, $year);
        $days = (string) $request->total_days;

        if ($request->status === LeaveRequestStatus::Approved) {
            // Restore used and current balance
            $newUsed = bcsub((string) $balance->used_days, $days, 2);
            $newCurrent = bcadd((string) $balance->current_balance, $days, 2);

            $balance->update([
                'row_version' => $balance->row_version + 1,
                'used_days' => max('0.00', $newUsed),
                'current_balance' => $newCurrent,
            ]);

            // Reset attendance records
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                $civilDate = $date->toDateString();
                $record = HrAttendanceRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employeeId)
                    ->whereDate('attendance_date', $civilDate)
                    ->first();

                if ($record !== null && $record->status === AttendanceStatus::OnLeave && $record->lock_status !== AttendanceLockStatus::Finalized) {
                    $record->update([
                        'status' => AttendanceStatus::Unresolved,
                        'notes' => 'Leave cancelled: '.$reason,
                    ]);
                }
            }
        } elseif ($request->status === LeaveRequestStatus::Pending) {
            $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
            $balance->update([
                'row_version' => $balance->row_version + 1,
                'pending_approval_days' => max('0.00', $newPending),
            ]);
        }

        $request->update([
            'row_version' => $request->row_version + 1,
            'status' => LeaveRequestStatus::Cancelled,
            'action_notes' => 'Cancelled: '.$reason,
        ]);

        return $request->refresh();
    }

    public function claimCompOff(int $tenantId, CompOffClaimData $data): HrCompOffClaim
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->employeeId)
            ->firstOrFail();

        return HrCompOffClaim::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
            'employee_id' => $data->employeeId,
            'worked_date' => $data->workedDate,
            'attendance_record_id' => $data->attendanceRecordId,
            'claimed_days' => $data->claimedDays,
            'reason' => $data->reason,
            'status' => CompOffStatus::Pending,
        ]);
    }

    public function actionCompOffClaim(HrCompOffClaim $claim, int $approverUserId, bool $approve): HrCompOffClaim
    {
        if ($claim->status !== CompOffStatus::Pending) {
            throw new InvalidArgumentException('Only pending comp-off claims can be actioned.');
        }

        $tenantId = (int) $claim->tenant_id;
        $employeeId = (int) $claim->employee_id;

        if ($approve) {
            // Find or create Compensatory leave type
            $compLeaveType = HrLeaveType::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'COMP_OFF',
                ],
                [
                    'name' => 'Compensatory Off',
                    'color' => '#8B5CF6',
                    'is_paid' => true,
                    'is_compensatory' => true,
                    'status' => 'active',
                ],
            );

            $year = CarbonImmutable::parse($claim->worked_date)->year;
            $balance = $this->getOrCreateBalance($tenantId, $employeeId, (int) $compLeaveType->getKey(), $year);

            $claimed = (string) $claim->claimed_days;
            $balance->update([
                'row_version' => $balance->row_version + 1,
                'accrued_days' => bcadd((string) $balance->accrued_days, $claimed, 2),
                'current_balance' => bcadd((string) $balance->current_balance, $claimed, 2),
            ]);

            $claim->update([
                'row_version' => $claim->row_version + 1,
                'status' => CompOffStatus::Approved,
                'expiry_date' => CarbonImmutable::parse($claim->worked_date)->addMonths(3)->toDateString(),
                'approved_by' => $approverUserId,
                'approved_at' => now(),
            ]);
        } else {
            $claim->update([
                'row_version' => $claim->row_version + 1,
                'status' => CompOffStatus::Rejected,
                'approved_by' => $approverUserId,
                'approved_at' => now(),
            ]);
        }

        return $claim->refresh();
    }
}
