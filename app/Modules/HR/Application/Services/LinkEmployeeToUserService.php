<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\LinkEmployeeToUserServiceInterface;
use Modules\HR\Domain\Events\EmployeeLinkedToUser;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class LinkEmployeeToUserService extends BaseService implements LinkEmployeeToUserServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): mixed
    {
        $id       = (int) ($data['id'] ?? 0);
        $employee = $this->employeeRepository->find($id);
        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        $employee->linkToUser((int) $data['user_id']);

        $saved = $this->employeeRepository->save($employee);
        $this->addEvent(new EmployeeLinkedToUser($saved));

        return $saved;
    }
}
