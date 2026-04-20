<?php

declare(strict_types=1);

namespace Modules\Employee\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Employee\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class DeleteEmployeeService extends BaseService implements DeleteEmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $employee = $this->employeeRepository->find($id);

        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        return $this->employeeRepository->delete($id);
    }
}
