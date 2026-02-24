<?php

namespace Modules\HR\Domain\Contracts;

interface AttendanceRecordRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findOpenCheckIn(string $tenantId, string $employeeId, string $workDate): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function paginate(array $filters = [], int $perPage = 15): object;
}
