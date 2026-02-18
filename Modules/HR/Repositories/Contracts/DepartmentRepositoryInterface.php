<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Department;

/**
 * Department Repository Interface
 *
 * Defines the contract for department data access operations.
 */
interface DepartmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find department by code.
     */
    public function findByCode(string $code): ?Department;

    /**
     * Get root departments (no parent).
     */
    public function getRootDepartments(): Collection;

    /**
     * Get child departments.
     */
    public function getChildren(int $parentId): Collection;

    /**
     * Get department tree with children.
     */
    public function getTree(): Collection;

    /**
     * Get active departments.
     */
    public function getActiveDepartments(): Collection;
}
