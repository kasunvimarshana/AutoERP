<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\ShiftAssignment;

interface ShiftAssignmentRepositoryInterface extends RepositoryInterface
{
    public function save(ShiftAssignment $assignment): ShiftAssignment;

    public function find(int|string $id, array $columns = ['*']): ?ShiftAssignment;

    public function findCurrentForEmployee(int $tenantId, int $employeeId, string $date): ?ShiftAssignment;
}
