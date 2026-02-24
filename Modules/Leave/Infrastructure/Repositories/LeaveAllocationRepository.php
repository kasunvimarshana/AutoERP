<?php

namespace Modules\Leave\Infrastructure\Repositories;

use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Infrastructure\Models\LeaveAllocationModel;

class LeaveAllocationRepository implements LeaveAllocationRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LeaveAllocationModel::find($id);
    }

    public function findApprovedByEmployeeAndType(
        string $tenantId,
        string $employeeId,
        string $leaveTypeId,
    ): ?object {
        return LeaveAllocationModel::where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->first();
    }

    public function create(array $data): object
    {
        return LeaveAllocationModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = LeaveAllocationModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        LeaveAllocationModel::findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = []): object
    {
        $query = LeaveAllocationModel::query();

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
