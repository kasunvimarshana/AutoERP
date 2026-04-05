<?php
declare(strict_types=1);
namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
class EloquentStockMovementRepository implements StockMovementRepositoryInterface {
    public function __construct(private readonly StockMovementModel $model) {}
    private function toEntity(StockMovementModel $m): StockMovement {
        return new StockMovement($m->id,$m->tenant_id,$m->product_id,$m->warehouse_id,$m->from_location_id,$m->to_location_id,
            $m->movement_type,(float)$m->quantity,(float)$m->unit_cost,$m->reference,$m->notes,$m->created_by,$m->moved_at,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?StockMovement { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByProduct(int $tenantId, int $productId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->where('product_id',$productId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function findByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->where('warehouse_id',$warehouseId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): StockMovement { return $this->toEntity($this->model->newQuery()->create($data)); }
}
