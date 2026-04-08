<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\HR\Domain\Entities\Employee;

interface FindEmployeeServiceInterface extends ReadServiceInterface
{
    /**
     * @return array<int, \Modules\HR\Domain\Entities\Employee>
     */
    public function getByDepartment(int $departmentId): array;

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Employee>
     */
    public function getByManager(int $managerId): array;

    public function findByUserId(int $userId): ?Employee;
}
