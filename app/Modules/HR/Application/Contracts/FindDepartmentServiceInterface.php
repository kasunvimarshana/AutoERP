<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindDepartmentServiceInterface extends ReadServiceInterface
{
    /**
     * Return all departments in nested-set order (flat array).
     *
     * @return array<int, \Modules\HR\Domain\Entities\Department>
     */
    public function getTree(): array;

    /**
     * Return all departments with the given parent (null = root departments).
     *
     * @return array<int, \Modules\HR\Domain\Entities\Department>
     */
    public function getByParent(?int $parentId): array;
}
