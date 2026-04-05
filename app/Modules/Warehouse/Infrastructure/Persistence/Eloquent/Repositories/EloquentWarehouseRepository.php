<?php
declare(strict_types=1);
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
class EloquentWarehouseRepository implements WarehouseRepositoryInterface {
    public function __construct(private readonly WarehouseModel $model) {}
    private function toEntity(WarehouseModel $m): Warehouse {
        return new Warehouse($m->id,$m->tenant_id,$m->name,$m->code,$m->type,$m->address,(bool)$m->is_active,$m->manager_id,$m->metadata,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?Warehouse { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByCode(int $tenantId, string $code): ?Warehouse { $m=$this->model->newQuery()->where('tenant_id',$tenantId)->where('code',$code)->first(); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): Warehouse { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?Warehouse { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
