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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Hr\DTOs\CompOffClaimData;
use Modules\Hr\DTOs\LeavePolicyData;
use Modules\Hr\DTOs\LeaveRequestData;
use Modules\Hr\DTOs\LeaveTypeData;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrCompOffClaim;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Models\HrLeavePolicy;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;

final class LeaveManagementService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

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
            'row_version' => 1,
        ])->refresh();
    }

    public function updateLeaveType(HrLeaveType $leaveType, LeaveTypeData $data): HrLeaveType
    {
        $leaveType->update([
            'row_version' => $leaveType->row_version + 1,
            'organization_unit_id' => $data->organizationUnitId ?? $leaveType->organization_unit_id,
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

        return $leaveType->refresh();
    }

    public function deleteLeaveType(HrLeaveType $leaveType): void
    {
        $hasUsage = HrLeaveRequest::query()
            ->where('tenant_id', $leaveType->tenant_id)
            ->where('leave_type_id', $leaveType->getKey())
            ->exists()
            || HrLeavePolicy::query()
                ->where('tenant_id', $leaveType->tenant_id)
                ->where('leave_type_id', $leaveType->getKey())
                ->exists();

        if ($hasUsage) {
            throw new InvalidArgumentException('Cannot delete leave type because it is referenced by leave policies or requests.');
        }

        $leaveType->delete();
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
            'row_version' => 1,
        ])->refresh();
    }

    public function updateLeavePolicy(HrLeavePolicy $policy, LeavePolicyData $data): HrLeavePolicy
    {
        $policy->update([
            'row_version' => $policy->row_version + 1,
            'organization_unit_id' => $data->organizationUnitId ?? $policy->organization_unit_id,
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

        return $policy->refresh();
    }

    public function deleteLeavePolicy(HrLeavePolicy $policy): void
    {
        $policy->delete();
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
        $startDate = CarbonImmutable::parse($data->startDate);
        $endDate = CarbonImmutable::parse($data->endDate);
        if ($endDate->lessThan($startDate)) {
            throw new InvalidArgumentException('Leave end date cannot be before start date.');
        }

        return DB::transaction(function () use ($tenantId, $data, $startDate, $endDate): HrLeaveRequest {
            $employee = HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data->employeeId)
                ->firstOrFail();

            $leaveType = HrLeaveType::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data->leaveTypeId)
                ->firstOrFail();

            if (in_array($data->durationType, [LeaveDurationType::HalfDayMorning, LeaveDurationType::HalfDayAfternoon], true) && ! $leaveType->allow_half_day) {
                throw new InvalidArgumentException("Leave type [{$leaveType->name}] does not allow half-day leave.");
            }
            if ($data->durationType === LeaveDurationType::CustomHours && ! $leaveType->allow_hourly) {
                throw new InvalidArgumentException("Leave type [{$leaveType->name}] does not allow hourly leave.");
            }
            if ($leaveType->requires_attachment
                && bccomp((string) $data->totalDays, (string) $leaveType->attachment_mandatory_after_days, 2) >= 0
                && $data->attachmentDocumentId === null) {
                throw new InvalidArgumentException("Supporting document attachment is required for [{$leaveType->name}] of {$data->totalDays} days.");
            }

            $hasOverlap = HrLeaveRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
                ->whereDate('start_date', '<=', $endDate->toDateString())
                ->whereDate('end_date', '>=', $startDate->toDateString())
                ->exists();

            if ($hasOverlap) {
                throw new InvalidArgumentException('Employee already has a pending or approved leave request overlapping these dates.');
            }

            $year = $startDate->year;
            $this->getOrCreateBalance($tenantId, $data->employeeId, $data->leaveTypeId, $year);

            $balance = HrLeaveBalance::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->where('leave_type_id', $data->leaveTypeId)
                ->where('calendar_year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $requestedDays = (string) $data->totalDays;
            $availableDays = bcsub((string) $balance->current_balance, (string) $balance->pending_approval_days, 2);

            if ($leaveType->is_paid && ! $leaveType->is_compensatory && bccomp($availableDays, $requestedDays, 2) < 0) {
                throw new InvalidArgumentException("Insufficient leave balance. Available: {$availableDays}, Requested: {$requestedDays}");
            }

            $balance->update([
                'row_version' => $balance->row_version + 1,
                'pending_approval_days' => bcadd((string) $balance->pending_approval_days, $requestedDays, 2),
            ]);

            return HrLeaveRequest::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
                'employee_id' => $data->employeeId,
                'leave_type_id' => $data->leaveTypeId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_type' => $data->durationType,
                'total_days' => $data->totalDays,
                'reason' => $data->reason,
                'attachment_document_id' => $data->attachmentDocumentId,
                'status' => LeaveRequestStatus::Pending,
            ]);
        });
    }

    public function actionLeaveRequest(
        HrLeaveRequest $request,
        int $approverUserId,
        bool $approve,
        ?string $notes = null,
    ): HrLeaveRequest {
        return DB::transaction(function () use ($request, $approverUserId, $approve, $notes): HrLeaveRequest {
            /** @var HrLeaveRequest $lockedRequest */
            $lockedRequest = HrLeaveRequest::query()
                ->where('tenant_id', $request->tenant_id)
                ->where('id', $request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== LeaveRequestStatus::Pending) {
                throw new InvalidArgumentException('Only pending leave requests can be actioned.');
            }

            $tenantId = (int) $lockedRequest->tenant_id;
            $employeeId = (int) $lockedRequest->employee_id;
            $year = CarbonImmutable::parse($lockedRequest->start_date)->year;
            $this->getOrCreateBalance($tenantId, $employeeId, (int) $lockedRequest->leave_type_id, $year);

            $balance = HrLeaveBalance::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', (int) $lockedRequest->leave_type_id)
                ->where('calendar_year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $days = (string) $lockedRequest->total_days;

            if ($approve) {
                $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
                $newUsed = bcadd((string) $balance->used_days, $days, 2);
                $newCurrent = bcsub((string) $balance->current_balance, $days, 2);

                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'pending_approval_days' => bccomp($newPending, '0.00', 2) < 0 ? '0.00' : $newPending,
                    'used_days' => $newUsed,
                    'current_balance' => $newCurrent,
                ]);

                $period = CarbonPeriod::create($lockedRequest->start_date, $lockedRequest->end_date);
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
                            'organization_unit_id' => $lockedRequest->organization_unit_id,
                            'status' => AttendanceStatus::OnLeave,
                            'lock_status' => AttendanceLockStatus::Open,
                        ]);
                    }

                    if ($record->lock_status !== AttendanceLockStatus::Finalized) {
                        $record->update([
                            'status' => AttendanceStatus::OnLeave,
                            'notes' => 'On Leave: approved leave request #'.$lockedRequest->getKey(),
                        ]);
                    }
                }
            } else {
                $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'pending_approval_days' => bccomp($newPending, '0.00', 2) < 0 ? '0.00' : $newPending,
                ]);
            }

            $lockedRequest->update([
                'row_version' => $lockedRequest->row_version + 1,
                'status' => $approve ? LeaveRequestStatus::Approved : LeaveRequestStatus::Rejected,
                'approver_id' => $approverUserId,
                'approved_at' => now(),
                'action_notes' => $notes,
            ]);

            return $lockedRequest->refresh();
        });
    }

    public function cancelLeaveRequest(HrLeaveRequest $request, int $cancellerUserId, string $reason): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $reason): HrLeaveRequest {
            /** @var HrLeaveRequest $lockedRequest */
            $lockedRequest = HrLeaveRequest::query()
                ->where('tenant_id', $request->tenant_id)
                ->where('id', $request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedRequest->status, [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Cannot cancel a leave request that is already rejected or cancelled.');
            }

            $tenantId = (int) $lockedRequest->tenant_id;
            $employeeId = (int) $lockedRequest->employee_id;
            $year = CarbonImmutable::parse($lockedRequest->start_date)->year;
            $this->getOrCreateBalance($tenantId, $employeeId, (int) $lockedRequest->leave_type_id, $year);

            $balance = HrLeaveBalance::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', (int) $lockedRequest->leave_type_id)
                ->where('calendar_year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $days = (string) $lockedRequest->total_days;

            if ($lockedRequest->status === LeaveRequestStatus::Approved) {
                $newUsed = bcsub((string) $balance->used_days, $days, 2);
                $newCurrent = bcadd((string) $balance->current_balance, $days, 2);

                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'used_days' => bccomp($newUsed, '0.00', 2) < 0 ? '0.00' : $newUsed,
                    'current_balance' => $newCurrent,
                ]);

                $lockedRequest->update([
                    'row_version' => $lockedRequest->row_version + 1,
                    'status' => LeaveRequestStatus::Cancelled,
                    'action_notes' => 'Cancelled: '.$reason,
                ]);

                $period = CarbonPeriod::create($lockedRequest->start_date, $lockedRequest->end_date);
                foreach ($period as $date) {
                    $civilDate = $date->toDateString();
                    $record = HrAttendanceRecord::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->whereDate('attendance_date', $civilDate)
                        ->first();

                    if ($record !== null && $record->status === AttendanceStatus::OnLeave && $record->lock_status !== AttendanceLockStatus::Finalized) {
                        $hasPunches = HrAttendancePunch::query()
                            ->where('tenant_id', $tenantId)
                            ->where('employee_id', $employeeId)
                            ->whereDate('attendance_date', $civilDate)
                            ->where('is_ignored', false)
                            ->exists();

                        if ($hasPunches) {
                            $this->calculationEngine->recalculateDailyRecord($tenantId, $employeeId, $civilDate);
                        } else {
                            $record->update([
                                'status' => AttendanceStatus::Unresolved,
                                'notes' => 'Leave cancelled: '.$reason,
                            ]);
                        }
                    }
                }

                return $lockedRequest->refresh();
            }

            if ($lockedRequest->status === LeaveRequestStatus::Pending) {
                $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'pending_approval_days' => bccomp($newPending, '0.00', 2) < 0 ? '0.00' : $newPending,
                ]);
            }

            $lockedRequest->update([
                'row_version' => $lockedRequest->row_version + 1,
                'status' => LeaveRequestStatus::Cancelled,
                'action_notes' => 'Cancelled: '.$reason,
            ]);

            return $lockedRequest->refresh();
        });
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
        return DB::transaction(function () use ($claim, $approverUserId, $approve): HrCompOffClaim {
            /** @var HrCompOffClaim $lockedClaim */
            $lockedClaim = HrCompOffClaim::query()
                ->where('tenant_id', $claim->tenant_id)
                ->where('id', $claim->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedClaim->status !== CompOffStatus::Pending) {
                throw new InvalidArgumentException('Only pending comp-off claims can be actioned.');
            }

            $tenantId = (int) $lockedClaim->tenant_id;
            $employeeId = (int) $lockedClaim->employee_id;

            if ($approve) {
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

                $year = CarbonImmutable::parse($lockedClaim->worked_date)->year;
                $this->getOrCreateBalance($tenantId, $employeeId, (int) $compLeaveType->getKey(), $year);

                $balance = HrLeaveBalance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', (int) $compLeaveType->getKey())
                    ->where('calendar_year', $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                $claimed = (string) $lockedClaim->claimed_days;
                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'accrued_days' => bcadd((string) $balance->accrued_days, $claimed, 2),
                    'current_balance' => bcadd((string) $balance->current_balance, $claimed, 2),
                ]);

                $lockedClaim->update([
                    'row_version' => $lockedClaim->row_version + 1,
                    'status' => CompOffStatus::Approved,
                    'expiry_date' => CarbonImmutable::parse($lockedClaim->worked_date)->addMonths(3)->toDateString(),
                    'approved_by' => $approverUserId,
                    'approved_at' => now(),
                ]);
            } else {
                $lockedClaim->update([
                    'row_version' => $lockedClaim->row_version + 1,
                    'status' => CompOffStatus::Rejected,
                    'approved_by' => $approverUserId,
                    'approved_at' => now(),
                ]);
            }

            return $lockedClaim->refresh();
        });
    }
}
