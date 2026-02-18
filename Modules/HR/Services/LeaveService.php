<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\BaseService;
use Modules\HR\Entities\Leave;
use Modules\HR\Events\LeaveApproved;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Repositories\Contracts\LeaveRepositoryInterface;
use Modules\HR\Repositories\Contracts\LeaveTypeRepositoryInterface;

/**
 * Leave Service
 *
 * Handles business logic for leave management, balance tracking, and approval workflow.
 */
class LeaveService extends BaseService
{
    /**
     * LeaveService constructor.
     */
    public function __construct(
        protected LeaveRepositoryInterface $leaveRepository,
        protected LeaveTypeRepositoryInterface $leaveTypeRepository,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Create a leave request.
     */
    public function create(array $data): Leave
    {
        return $this->executeInTransaction(function () use ($data) {
            // Calculate number of days if not provided
            if (empty($data['days'])) {
                $startDate = Carbon::parse($data['start_date']);
                $endDate = Carbon::parse($data['end_date']);
                $data['days'] = $startDate->diffInDays($endDate) + 1;
            }

            // Check if employee has sufficient leave balance
            $this->validateLeaveBalance(
                $data['employee_id'],
                $data['leave_type_id'],
                $data['days']
            );

            $leave = $this->leaveRepository->create($data);

            $this->logInfo('Leave request created', [
                'leave_id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'days' => $leave->days,
            ]);

            return $leave;
        });
    }

    /**
     * Update a leave request.
     */
    public function update(int $id, array $data): Leave
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $leave = $this->leaveRepository->update($id, $data);

            $this->logInfo('Leave request updated', ['leave_id' => $leave->id]);

            return $leave;
        });
    }

    /**
     * Approve a leave request.
     */
    public function approve(int $id, int $approverId): Leave
    {
        return $this->executeInTransaction(function () use ($id, $approverId) {
            $leave = $this->leaveRepository->update($id, [
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => Carbon::now(),
            ]);

            event(new LeaveApproved($leave));

            $this->logInfo('Leave request approved', [
                'leave_id' => $leave->id,
                'approved_by' => $approverId,
            ]);

            return $leave;
        });
    }

    /**
     * Reject a leave request.
     */
    public function reject(int $id, int $approverId, string $reason): Leave
    {
        return $this->executeInTransaction(function () use ($id, $approverId, $reason) {
            $leave = $this->leaveRepository->update($id, [
                'status' => 'rejected',
                'approved_by' => $approverId,
                'approved_at' => Carbon::now(),
                'rejection_reason' => $reason,
            ]);

            $this->logInfo('Leave request rejected', [
                'leave_id' => $leave->id,
                'rejected_by' => $approverId,
            ]);

            return $leave;
        });
    }

    /**
     * Get leave balance for an employee.
     */
    public function getLeaveBalance(int $employeeId, int $leaveTypeId, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $leaveType = $this->leaveTypeRepository->find($leaveTypeId);

        if (! $leaveType) {
            throw new \RuntimeException('Leave type not found.');
        }

        $usedDays = $this->leaveRepository->getLeaveBalance($employeeId, $leaveTypeId, $year);
        $totalDays = $leaveType->max_days_per_year;
        $remainingDays = max(0, $totalDays - $usedDays);

        return [
            'leave_type' => $leaveType->name,
            'total_days' => $totalDays,
            'used_days' => $usedDays,
            'remaining_days' => $remainingDays,
            'year' => $year,
        ];
    }

    /**
     * Get all leave balances for an employee.
     */
    public function getAllLeaveBalances(int $employeeId, ?int $year = null): Collection
    {
        $year = $year ?? Carbon::now()->year;
        $leaveTypes = $this->leaveTypeRepository->getActiveLeaveTypes();

        return $leaveTypes->map(function ($leaveType) use ($employeeId, $year) {
            return $this->getLeaveBalance($employeeId, $leaveType->id, $year);
        });
    }

    /**
     * Get leaves by employee.
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->leaveRepository->getByEmployee($employeeId);
    }

    /**
     * Get pending leaves.
     */
    public function getPendingLeaves(): Collection
    {
        return $this->leaveRepository->getPendingLeaves();
    }

    /**
     * Validate leave balance before creating a leave request.
     */
    protected function validateLeaveBalance(int $employeeId, int $leaveTypeId, float $requestedDays): void
    {
        $balance = $this->getLeaveBalance($employeeId, $leaveTypeId);

        if ($requestedDays > $balance['remaining_days']) {
            throw new \RuntimeException(
                "Insufficient leave balance. Requested: {$requestedDays} days, Available: {$balance['remaining_days']} days."
            );
        }
    }

    /**
     * Find leave by ID.
     */
    public function findById(int $id): ?Leave
    {
        return $this->leaveRepository->find($id);
    }

    /**
     * Delete leave record.
     */
    public function delete(int $id): bool
    {
        return $this->leaveRepository->delete($id);
    }
}
