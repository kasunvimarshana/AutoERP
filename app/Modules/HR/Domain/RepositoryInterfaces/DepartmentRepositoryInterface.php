<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Department;

interface DepartmentRepositoryInterface extends RepositoryInterface
{
    public function save(Department $department): Department;

    /**
     * Return all departments in nested-set order (flat array).
     *
     * @return array<int, Department>
     */
    public function getTree(): array;

    /**
     * Return all departments with the given parent (null = root departments).
     *
     * @return array<int, Department>
     */
    public function getByParent(?int $parentId): array;
}
