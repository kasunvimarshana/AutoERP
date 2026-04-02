<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\HR\Domain\Events\EmployeeDeleted;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class DeleteEmployeeService extends BaseService implements DeleteEmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $employee = $this->employeeRepository->find($id);
        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }
        $tenantId = $employee->getTenantId();
        $deleted  = $this->employeeRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new EmployeeDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
