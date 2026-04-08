<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindEmployeeServiceInterface;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class FindEmployeeService extends BaseService implements FindEmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Employee>
     */
    public function getByDepartment(int $departmentId): array
    {
        return $this->employeeRepository->getByDepartment($departmentId);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Employee>
     */
    public function getByManager(int $managerId): array
    {
        return $this->employeeRepository->getByManager($managerId);
    }

    public function findByUserId(int $userId): ?Employee
    {
        return $this->employeeRepository->findByUserId($userId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
