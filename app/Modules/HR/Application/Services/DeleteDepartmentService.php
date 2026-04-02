<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteDepartmentServiceInterface;
use Modules\HR\Domain\Events\DepartmentDeleted;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class DeleteDepartmentService extends BaseService implements DeleteDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $departmentRepository)
    {
        parent::__construct($departmentRepository);
    }

    protected function handle(array $data): bool
    {
        $id         = $data['id'];
        $department = $this->departmentRepository->find($id);
        if (! $department) {
            throw new DepartmentNotFoundException($id);
        }
        $tenantId = $department->getTenantId();
        $deleted  = $this->departmentRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new DepartmentDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
