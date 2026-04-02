<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class GetEmployee
{
    public function __construct(private readonly EmployeeRepositoryInterface $repo) {}

    public function execute(int $id): ?Employee
    {
        return $this->repo->find($id);
    }
}
