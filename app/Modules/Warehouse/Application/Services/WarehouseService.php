<?php
declare(strict_types=1);
namespace Modules\Warehouse\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
class WarehouseService implements WarehouseServiceInterface {
    public function __construct(private readonly WarehouseRepositoryInterface $repo) {}
    public function findById(int $id): Warehouse { $w=$this->repo->findById($id); if(!$w) throw new NotFoundException("Warehouse", $id); return $w; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator { return $this->repo->findByTenant($tenantId,$perPage,$page); }
    public function create(array $data): Warehouse { return $this->repo->create($data); }
    public function update(int $id, array $data): Warehouse { $w=$this->repo->update($id,$data); if(!$w) throw new NotFoundException("Warehouse", $id); return $w; }
    public function delete(int $id): bool { $w=$this->repo->findById($id); if(!$w) throw new NotFoundException("Warehouse", $id); return $this->repo->delete($id); }
}
