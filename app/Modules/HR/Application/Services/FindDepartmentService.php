<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindDepartmentServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class FindDepartmentService extends BaseService implements FindDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $departmentRepository)
    {
        parent::__construct($departmentRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Department>
     */
    public function getTree(): array
    {
        return $this->departmentRepository->getTree();
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Department>
     */
    public function getByParent(?int $parentId): array
    {
        return $this->departmentRepository->getByParent($parentId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
