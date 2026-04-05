<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\DepartmentServiceInterface;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class DepartmentService implements DepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $repository) {}

    public function findById(int $id): Department
    {
        $dept = $this->repository->findById($id);
        if ($dept === null) {
            throw new DepartmentNotFoundException($id);
        }
        return $dept;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    public function create(array $data): Department
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Department
    {
        $dept = $this->findById($id);
        $updated = $this->repository->update($id, $data);
        return $updated ?? $dept;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
