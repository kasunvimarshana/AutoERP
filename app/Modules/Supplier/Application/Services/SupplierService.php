<?php
declare(strict_types=1);
namespace Modules\Supplier\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
class SupplierService implements SupplierServiceInterface {
    public function __construct(private readonly SupplierRepositoryInterface $repo) {}
    public function findById(int $id): Supplier {
        $e = $this->repo->findById($id);
        if (!$e) throw new SupplierNotFoundException($id);
        return $e;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }
    public function create(array $data): Supplier { return $this->repo->create($data); }
    public function update(int $id, array $data): Supplier {
        $e = $this->repo->update($id, $data);
        if (!$e) throw new SupplierNotFoundException($id);
        return $e;
    }
    public function delete(int $id): bool {
        $e = $this->repo->findById($id);
        if (!$e) throw new SupplierNotFoundException($id);
        return $this->repo->delete($id);
    }
}
