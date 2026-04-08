<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class GetDepartment
{
    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    public function execute(int $id): ?Department
    {
        return $this->repo->find($id);
    }
}
