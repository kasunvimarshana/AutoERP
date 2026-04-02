<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\EmployeeDeleted;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class DeleteEmployee
{
    public function __construct(private readonly EmployeeRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $employee = $this->repo->find($id);
        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        $tenantId = $employee->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            EmployeeDeleted::dispatch($id, $tenantId);
        }

        return $deleted;
    }
}
