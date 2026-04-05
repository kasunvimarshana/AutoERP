<?php
declare(strict_types=1);
namespace Modules\Warehouse\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
class WarehouseLocationService implements WarehouseLocationServiceInterface {
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repo) {}
    public function findById(int $id): WarehouseLocation { $l=$this->repo->findById($id); if(!$l) throw new NotFoundException("WarehouseLocation", $id); return $l; }
    public function findByWarehouse(int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator { return $this->repo->findByWarehouse($warehouseId,$perPage,$page); }
    public function getTree(int $warehouseId): array { return $this->repo->buildTree($warehouseId); }
    public function create(array $data): WarehouseLocation { return $this->repo->create($data); }
    public function update(int $id, array $data): WarehouseLocation { $l=$this->repo->update($id,$data); if(!$l) throw new NotFoundException("WarehouseLocation", $id); return $l; }
    public function delete(int $id): bool { $l=$this->repo->findById($id); if(!$l) throw new NotFoundException("WarehouseLocation", $id); return $this->repo->delete($id); }
}
