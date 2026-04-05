<?php
declare(strict_types=1);
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
class EloquentWarehouseLocationRepository implements WarehouseLocationRepositoryInterface {
    public function __construct(private readonly WarehouseLocationModel $model) {}
    private function toEntity(WarehouseLocationModel $m): WarehouseLocation {
        return new WarehouseLocation($m->id,$m->tenant_id,$m->warehouse_id,$m->parent_id,$m->name,$m->code,$m->type,(int)$m->level,(bool)$m->is_active,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?WarehouseLocation { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByWarehouse(int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('warehouse_id',$warehouseId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function findByParent(int $warehouseId, ?int $parentId): array {
        return $this->model->newQuery()->where('warehouse_id',$warehouseId)->where('parent_id',$parentId)->get()->map(fn($m)=>$this->toEntity($m))->all();
    }
    public function create(array $data): WarehouseLocation { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?WarehouseLocation { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
    public function buildTree(int $warehouseId): array {
        $all=$this->model->newQuery()->with('children')->where('warehouse_id',$warehouseId)->whereNull('parent_id')->get();
        return $this->buildFromNodes($all);
    }
    private function buildFromNodes($nodes): array {
        return $nodes->map(function($n) {
            $e=$this->toEntity($n);
            return ['id'=>$e->getId(),'name'=>$e->getName(),'code'=>$e->getCode(),'type'=>$e->getType(),'level'=>$e->getLevel(),'children'=>$this->buildFromNodes($n->children)];
        })->all();
    }
}
