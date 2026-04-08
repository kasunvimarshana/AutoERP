<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindPositionServiceInterface extends ReadServiceInterface
{
    /**
     * Return all positions belonging to a given department.
     *
     * @return array<int, \Modules\HR\Domain\Entities\Position>
     */
    public function getByDepartment(int $departmentId): array;
}
