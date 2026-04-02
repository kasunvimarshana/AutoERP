<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Position;

interface PositionRepositoryInterface extends RepositoryInterface
{
    public function save(Position $position): Position;

    /**
     * Return all positions belonging to a given department.
     *
     * @return array<int, Position>
     */
    public function getByDepartment(int $departmentId): array;
}
