<?php
declare(strict_types=1);
namespace Modules\UoM\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Application\Contracts\UnitOfMeasureServiceInterface;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
class UnitOfMeasureService implements UnitOfMeasureServiceInterface {
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repo) {}
    public function findById(int $id): UnitOfMeasure {
        $e = $this->repo->findById($id);
        if (!$e) throw new NotFoundException("UnitOfMeasure", $id);
        return $e;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }
    public function create(array $data): UnitOfMeasure { return $this->repo->create($data); }
    public function update(int $id, array $data): UnitOfMeasure {
        $e = $this->repo->update($id, $data);
        if (!$e) throw new NotFoundException("UnitOfMeasure", $id);
        return $e;
    }
    public function delete(int $id): bool {
        $e = $this->repo->findById($id);
        if (!$e) throw new NotFoundException("UnitOfMeasure", $id);
        return $this->repo->delete($id);
    }
}
