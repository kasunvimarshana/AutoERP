<?php

namespace Modules\Leave\Domain\Contracts;

interface LeaveAllocationRepositoryInterface
{
    public function findById(string $id): ?object;

    /**
     * Find an approved allocation for the given employee and leave type.
     * Used to check remaining balance before a leave request is created.
     */
    public function findApprovedByEmployeeAndType(
        string $tenantId,
        string $employeeId,
        string $leaveTypeId,
    ): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function delete(string $id): void;

    public function paginate(int $perPage = 15, array $filters = []): object;
}
