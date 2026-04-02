<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\DepartmentDeleted;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class DeleteDepartment
{
    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $department = $this->repo->find($id);
        if (! $department) {
            throw new DepartmentNotFoundException($id);
        }

        $tenantId = $department->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            DepartmentDeleted::dispatch($id, $tenantId);
        }

        return $deleted;
    }
}
