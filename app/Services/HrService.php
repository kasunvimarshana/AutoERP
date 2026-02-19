<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HrService
{
    public function paginateEmployees(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::where('tenant_id', $tenantId)
            ->with(['department', 'user']);

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('last_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%')
                    ->orWhere('employee_number', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }

    public function createEmployee(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            return Employee::create($data);
        });
    }

    public function updateEmployee(string $id, array $data): Employee
    {
        return DB::transaction(function () use ($id, $data) {
            $employee = Employee::findOrFail($id);
            $employee->update($data);

            return $employee->fresh(['department', 'user']);
        });
    }

    public function terminateEmployee(string $id, ?string $terminationDate = null): Employee
    {
        return DB::transaction(function () use ($id, $terminationDate) {
            $employee = Employee::findOrFail($id);
            $employee->update([
                'status' => EmployeeStatus::Terminated,
                'termination_date' => $terminationDate ?? now()->toDateString(),
            ]);

            return $employee->fresh();
        });
    }

    public function paginateDepartments(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Department::where('tenant_id', $tenantId)
            ->with(['parent', 'manager'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createDepartment(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            return Department::create($data);
        });
    }

    public function updateDepartment(string $id, array $data): Department
    {
        return DB::transaction(function () use ($id, $data) {
            $department = Department::findOrFail($id);
            $department->update($data);

            return $department->fresh(['parent', 'manager']);
        });
    }

    public function paginateLeaveRequests(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LeaveRequest::where('tenant_id', $tenantId)
            ->with(['employee', 'leaveType', 'approver']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createLeaveRequest(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $data['status'] ??= LeaveRequestStatus::Pending->value;

            return LeaveRequest::create($data);
        });
    }

    public function approveLeaveRequest(string $id, string $approverId): LeaveRequest
    {
        return DB::transaction(function () use ($id, $approverId) {
            $leaveRequest = LeaveRequest::findOrFail($id);
            $leaveRequest->update([
                'status' => LeaveRequestStatus::Approved,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
        });
    }

    public function rejectLeaveRequest(string $id, string $approverId): LeaveRequest
    {
        return DB::transaction(function () use ($id, $approverId) {
            $leaveRequest = LeaveRequest::findOrFail($id);
            $leaveRequest->update([
                'status' => LeaveRequestStatus::Rejected,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return $leaveRequest->fresh(['employee', 'leaveType', 'approver']);
        });
    }
}
