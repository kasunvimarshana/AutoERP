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
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrCompOffClaim;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Models\HrLeavePolicy;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftRotationAssignment;

final class LeaveManagementService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly ShiftRosterService $rosterService,
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
                ->exists()
            || HrLeaveBalance::query()
                ->where('tenant_id', $leaveType->tenant_id)
                ->where('leave_type_id', $leaveType->getKey())
                ->exists();

        if ($hasUsage) {
            throw new InvalidArgumentException('Cannot delete leave type because it is referenced by leave policies, balances, or requests.');
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
                ->with('employmentType')
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

            $policy = $this->resolveActivePolicy($tenantId, $employee, (int) $leaveType->getKey(), $startDate, $endDate);
            if ($policy !== null && $policy->probation_restricted && $this->isEmployeeOnProbation($employee, $startDate)) {
                throw new InvalidArgumentException("Leave type [{$leaveType->name}] is restricted during probation.");
            }

            $daysByYear = $this->computeLeaveDaysByYear(
                $tenantId,
                $data->employeeId,
                $startDate,
                $endDate,
                $data->durationType,
                (bool) $leaveType->sandwich_rule_applied,
                $data->totalDays,
            );

            $computedTotalDays = '0.00';
            foreach ($daysByYear as $yearDays) {
                $computedTotalDays = bcadd($computedTotalDays, $yearDays, 2);
            }

            if ($policy !== null && (int) $policy->max_consecutive_days > 0
                && bccomp($computedTotalDays, (string) $policy->max_consecutive_days, 2) > 0) {
                throw new InvalidArgumentException(
                    "Requested leave ({$computedTotalDays} days) exceeds policy maximum consecutive days ({$policy->max_consecutive_days})."
                );
            }

            if ($leaveType->requires_attachment
                && bccomp($computedTotalDays, (string) $leaveType->attachment_mandatory_after_days, 2) >= 0
                && $data->attachmentDocumentId === null) {
                throw new InvalidArgumentException("Supporting document attachment is required for [{$leaveType->name}] of {$computedTotalDays} days.");
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

            ksort($daysByYear);
            foreach ($daysByYear as $year => $yearDays) {
                $this->getOrCreateBalance($tenantId, $data->employeeId, $data->leaveTypeId, (int) $year);

                $balance = HrLeaveBalance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $data->employeeId)
                    ->where('leave_type_id', $data->leaveTypeId)
                    ->where('calendar_year', (int) $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($policy !== null && $leaveType->is_paid && ! $leaveType->is_compensatory) {
                    $isFreshZeroBalance = bccomp((string) $balance->opening_balance, '0.00', 2) === 0
                        && bccomp((string) $balance->accrued_days, '0.00', 2) === 0
                        && bccomp((string) $balance->carried_forward_days, '0.00', 2) === 0
                        && bccomp((string) $balance->used_days, '0.00', 2) === 0
                        && bccomp((string) $balance->pending_approval_days, '0.00', 2) === 0
                        && bccomp((string) $balance->current_balance, '0.00', 2) === 0;

                    if ($isFreshZeroBalance) {
                        $allocation = number_format((float) $policy->annual_allocation_days, 2, '.', '');
                        $balance->update([
                            'row_version' => $balance->row_version + 1,
                            'accrued_days' => $allocation,
                            'current_balance' => $allocation,
                        ]);
                        $balance->refresh();
                    }

                    $maxAllowedForYear = bcadd(
                        bcadd((string) $policy->annual_allocation_days, (string) $balance->carried_forward_days, 2),
                        (string) $balance->opening_balance,
                        2,
                    );
                    $projectedYearUsage = bcadd(
                        bcadd((string) $balance->used_days, (string) $balance->pending_approval_days, 2),
                        $yearDays,
                        2,
                    );

                    if (bccomp($projectedYearUsage, $maxAllowedForYear, 2) > 0) {
                        throw new InvalidArgumentException("Requested leave exceeds annual allocation limit ({$maxAllowedForYear} days) for {$year}.");
                    }
                }

                $availableDays = bcsub((string) $balance->current_balance, (string) $balance->pending_approval_days, 2);

                if ($leaveType->is_paid && ! $leaveType->is_compensatory && bccomp($availableDays, $yearDays, 2) < 0) {
                    throw new InvalidArgumentException("Insufficient leave balance for {$year}. Available: {$availableDays}, Requested: {$yearDays}");
                }

                $balance->update([
                    'row_version' => $balance->row_version + 1,
                    'pending_approval_days' => bcadd((string) $balance->pending_approval_days, $yearDays, 2),
                ]);
            }

            return HrLeaveRequest::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
                'employee_id' => $data->employeeId,
                'leave_type_id' => $data->leaveTypeId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_type' => $data->durationType,
                'total_days' => $computedTotalDays,
                'reason' => $data->reason,
                'attachment_document_id' => $data->attachmentDocumentId,
                'status' => LeaveRequestStatus::Pending,
                'row_version' => 1,
            ])->refresh();
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
                ->with('leaveType')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== LeaveRequestStatus::Pending) {
                throw new InvalidArgumentException('Only pending leave requests can be actioned.');
            }

            $tenantId = (int) $lockedRequest->tenant_id;
            $employeeId = (int) $lockedRequest->employee_id;
            $daysByYear = $this->splitExistingRequestDaysByYear($lockedRequest);

            foreach ($daysByYear as $year => $days) {
                $this->getOrCreateBalance($tenantId, $employeeId, (int) $lockedRequest->leave_type_id, (int) $year);

                $balance = HrLeaveBalance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', (int) $lockedRequest->leave_type_id)
                    ->where('calendar_year', (int) $year)
                    ->lockForUpdate()
                    ->firstOrFail();

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
                } else {
                    $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
                    $balance->update([
                        'row_version' => $balance->row_version + 1,
                        'pending_approval_days' => bccomp($newPending, '0.00', 2) < 0 ? '0.00' : $newPending,
                    ]);
                }
            }

            if ($approve) {
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
                ->with('leaveType')
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedRequest->status, [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Cannot cancel a leave request that is already rejected or cancelled.');
            }

            $tenantId = (int) $lockedRequest->tenant_id;
            $employeeId = (int) $lockedRequest->employee_id;
            $daysByYear = $this->splitExistingRequestDaysByYear($lockedRequest);

            foreach ($daysByYear as $year => $days) {
                $this->getOrCreateBalance($tenantId, $employeeId, (int) $lockedRequest->leave_type_id, (int) $year);

                $balance = HrLeaveBalance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', (int) $lockedRequest->leave_type_id)
                    ->where('calendar_year', (int) $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status === LeaveRequestStatus::Approved) {
                    $newUsed = bcsub((string) $balance->used_days, $days, 2);
                    $newCurrent = bcadd((string) $balance->current_balance, $days, 2);

                    $balance->update([
                        'row_version' => $balance->row_version + 1,
                        'used_days' => bccomp($newUsed, '0.00', 2) < 0 ? '0.00' : $newUsed,
                        'current_balance' => $newCurrent,
                    ]);
                } elseif ($lockedRequest->status === LeaveRequestStatus::Pending) {
                    $newPending = bcsub((string) $balance->pending_approval_days, $days, 2);
                    $balance->update([
                        'row_version' => $balance->row_version + 1,
                        'pending_approval_days' => bccomp($newPending, '0.00', 2) < 0 ? '0.00' : $newPending,
                    ]);
                }
            }

            if ($lockedRequest->status === LeaveRequestStatus::Approved) {
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
        $workedDate = CarbonImmutable::parse($data->workedDate)->toDateString();

        return DB::transaction(function () use ($tenantId, $data, $workedDate): HrCompOffClaim {
            $employee = HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data->employeeId)
                ->firstOrFail();

            $duplicateExists = HrCompOffClaim::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->whereDate('worked_date', $workedDate)
                ->whereIn('status', [CompOffStatus::Pending, CompOffStatus::Approved, CompOffStatus::Used])
                ->exists();

            if ($duplicateExists) {
                throw new InvalidArgumentException('A compensatory off claim already exists for this worked date.');
            }

            $attendanceQuery = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->whereDate('attendance_date', $workedDate);

            if ($data->attendanceRecordId !== null) {
                $attendanceQuery->where('id', $data->attendanceRecordId);
            }

            $record = $attendanceQuery->first();

            $disallowedStatuses = [
                AttendanceStatus::Absent,
                AttendanceStatus::OnLeave,
                AttendanceStatus::Unresolved,
                AttendanceStatus::MissingPunch,
            ];

            if ($record === null
                || (int) $record->total_worked_minutes <= 0
                || in_array($record->status, $disallowedStatuses, true)) {
                throw new InvalidArgumentException('No qualifying worked attendance record found for the specified worked date.');
            }

            $resolved = $this->rosterService->resolveShift($tenantId, $data->employeeId, $workedDate);
            $hasSchedule = $this->employeeHasConfiguredSchedule($tenantId, $data->employeeId);
            $isHolidayOrRestDay = $resolved->isHoliday
                || ($hasSchedule && $resolved->isRestDay && ! $resolved->isWorkingDay)
                || in_array($record->status, [AttendanceStatus::Holiday, AttendanceStatus::RestDay], true);

            if (! $isHolidayOrRestDay) {
                throw new InvalidArgumentException('Compensatory off can only be claimed for work performed on a holiday or rest day.');
            }

            return HrCompOffClaim::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
                'employee_id' => $data->employeeId,
                'worked_date' => $workedDate,
                'attendance_record_id' => $record->getKey(),
                'claimed_days' => $data->claimedDays,
                'reason' => $data->reason,
                'status' => CompOffStatus::Pending,
                'row_version' => 1,
            ])->refresh();
        });
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

    private function resolveActivePolicy(
        int $tenantId,
        HrEmployee $employee,
        int $leaveTypeId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): ?HrLeavePolicy {
        $scopedPolicies = HrLeavePolicy::query()
            ->where('tenant_id', $tenantId)
            ->where('leave_type_id', $leaveTypeId)
            ->where(static function ($q) use ($employee): void {
                $q->whereNull('department_id');
                if ($employee->department_id !== null) {
                    $q->orWhere('department_id', $employee->department_id);
                }
            })
            ->where(static function ($q) use ($employee): void {
                $q->whereNull('employment_type_id');
                if ($employee->employment_type_id !== null) {
                    $q->orWhere('employment_type_id', $employee->employment_type_id);
                }
            })
            ->orderByRaw('CASE WHEN department_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN employment_type_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        if ($scopedPolicies->isEmpty()) {
            return null;
        }

        $startStr = $startDate->toDateString();
        $endStr = $endDate->toDateString();

        $activePolicy = $scopedPolicies->first(static function (HrLeavePolicy $policy) use ($startStr, $endStr): bool {
            $from = CarbonImmutable::parse($policy->effective_from)->toDateString();
            $to = $policy->effective_to !== null ? CarbonImmutable::parse($policy->effective_to)->toDateString() : null;

            return $from <= $startStr && ($to === null || $to >= $endStr);
        });

        if ($activePolicy === null) {
            throw new InvalidArgumentException('No active leave policy covers the requested leave dates.');
        }

        return $activePolicy;
    }

    private function isEmployeeOnProbation(HrEmployee $employee, CarbonImmutable $asOfDate): bool
    {
        $empType = $employee->employmentType;
        if ($empType !== null) {
            $code = strtolower((string) $empType->code);
            $name = strtolower((string) $empType->name);
            if (str_contains($code, 'probation') || str_contains($name, 'probation')) {
                return true;
            }
        }

        $metadata = is_array($employee->metadata) ? $employee->metadata : [];
        if (($metadata['is_probation'] ?? false) === true || ($metadata['on_probation'] ?? false) === true) {
            return true;
        }

        if (! empty($metadata['probation_end_date'])) {
            $probationEnd = CarbonImmutable::parse((string) $metadata['probation_end_date']);
            if ($asOfDate->lessThanOrEqualTo($probationEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function computeLeaveDaysByYear(
        int $tenantId,
        int $employeeId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        LeaveDurationType $durationType,
        bool $sandwichRuleApplied,
        ?string $customDays = null,
    ): array {
        if (in_array($durationType, [LeaveDurationType::HalfDayMorning, LeaveDurationType::HalfDayAfternoon], true)) {
            if ($startDate->toDateString() !== $endDate->toDateString()) {
                throw new InvalidArgumentException('Half-day leave must start and end on the same date.');
            }

            if (! $sandwichRuleApplied) {
                $resolved = $this->rosterService->resolveShift($tenantId, $employeeId, $startDate->toDateString());
                $hasSchedule = $this->employeeHasConfiguredSchedule($tenantId, $employeeId);
                if ($resolved->isHoliday || ($hasSchedule && $resolved->isRestDay && ! $resolved->isWorkingDay)) {
                    throw new InvalidArgumentException('Cannot apply half-day leave on a holiday or rest day.');
                }
            }

            return [$startDate->year => '0.50'];
        }

        if ($durationType === LeaveDurationType::CustomHours) {
            if ($startDate->toDateString() !== $endDate->toDateString()) {
                throw new InvalidArgumentException('Hourly leave must start and end on the same date.');
            }
            if ($customDays === null || bccomp($customDays, '0.00', 2) <= 0 || bccomp($customDays, '1.00', 2) > 0) {
                throw new InvalidArgumentException('Hourly leave duration must be greater than 0 and at most 1.00 day.');
            }

            return [$startDate->year => number_format((float) $customDays, 2, '.', '')];
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $byYear = [];

        if ($sandwichRuleApplied) {
            foreach ($period as $day) {
                $year = (int) $day->year;
                $byYear[$year] = bcadd($byYear[$year] ?? '0.00', '1.00', 2);
            }

            return $byYear;
        }

        $roster = $this->rosterService->resolveRosterBatch(
            $tenantId,
            [$employeeId],
            $startDate->toDateString(),
            $endDate->toDateString(),
        );
        $employeeRoster = $roster[$employeeId] ?? [];
        $hasSchedule = $this->employeeHasConfiguredSchedule($tenantId, $employeeId);

        foreach ($period as $day) {
            $civilDate = $day->toDateString();
            $resolved = $employeeRoster[$civilDate] ?? null;

            if ($resolved !== null && $resolved->isHoliday) {
                continue;
            }
            if ($hasSchedule && $resolved !== null && $resolved->isRestDay && ! $resolved->isWorkingDay) {
                continue;
            }

            $year = (int) $day->year;
            $byYear[$year] = bcadd($byYear[$year] ?? '0.00', '1.00', 2);
        }

        if ($byYear === []) {
            throw new InvalidArgumentException('Selected leave dates contain no working days.');
        }

        return $byYear;
    }

    private function employeeHasConfiguredSchedule(int $tenantId, int $employeeId): bool
    {
        return HrShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('assignment_type', ShiftAssignmentType::Recurring)
            ->where('is_cancelled', false)
            ->exists()
            || HrShiftRotationAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employeeId)
                ->where('is_active', true)
                ->exists();
    }

    /**
     * @return array<int, string>
     */
    private function splitExistingRequestDaysByYear(HrLeaveRequest $request): array
    {
        $startDate = CarbonImmutable::parse($request->start_date);
        $endDate = CarbonImmutable::parse($request->end_date);
        $totalDays = number_format((float) $request->total_days, 2, '.', '');

        if ($startDate->year === $endDate->year) {
            return [$startDate->year => $totalDays];
        }

        try {
            $byYear = $this->computeLeaveDaysByYear(
                (int) $request->tenant_id,
                (int) $request->employee_id,
                $startDate,
                $endDate,
                $request->duration_type,
                (bool) ($request->leaveType?->sandwich_rule_applied ?? false),
                $totalDays,
            );
        } catch (InvalidArgumentException) {
            return [$startDate->year => $totalDays];
        }

        $computedSum = '0.00';
        foreach ($byYear as $days) {
            $computedSum = bcadd($computedSum, $days, 2);
        }

        if (bccomp($computedSum, $totalDays, 2) !== 0 && $byYear !== []) {
            $lastYear = (int) array_key_last($byYear);
            $diff = bcsub($totalDays, $computedSum, 2);
            $adjusted = bcadd($byYear[$lastYear], $diff, 2);
            if (bccomp($adjusted, '0.00', 2) >= 0) {
                $byYear[$lastYear] = $adjusted;
            } else {
                return [$startDate->year => $totalDays];
            }
        }

        return $byYear;
    }
}
