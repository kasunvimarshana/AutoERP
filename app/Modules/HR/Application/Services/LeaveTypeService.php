<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\LeaveTypeServiceInterface;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\Exceptions\LeaveTypeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;

class LeaveTypeService implements LeaveTypeServiceInterface
{
    public function __construct(private readonly LeaveTypeRepositoryInterface $repository) {}

    public function findById(int $id): LeaveType
    {
        $type = $this->repository->findById($id);
        if ($type === null) {
            throw new LeaveTypeNotFoundException($id);
        }
        return $type;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findAllActiveByTenant(int $tenantId): array
    {
        return $this->repository->findAllActiveByTenant($tenantId);
    }

    public function create(array $data): LeaveType
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): LeaveType
    {
        $this->findById($id);
        $updated = $this->repository->update($id, $data);
        return $updated ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
