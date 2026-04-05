<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\PositionServiceInterface;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class PositionService implements PositionServiceInterface
{
    public function __construct(private readonly PositionRepositoryInterface $repository) {}

    public function findById(int $id): Position
    {
        $pos = $this->repository->findById($id);
        if ($pos === null) {
            throw new PositionNotFoundException($id);
        }
        return $pos;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findByDepartment(int $departmentId): array
    {
        return $this->repository->findByDepartment($departmentId);
    }

    public function create(array $data): Position
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Position
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
