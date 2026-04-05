<?php
declare(strict_types=1);
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;
class EloquentUomCategoryRepository implements UomCategoryRepositoryInterface {
    public function __construct(private readonly UomCategoryModel $model) {}
    private function toEntity(UomCategoryModel $m): UomCategory {
        return new UomCategory($m->id, $m->tenant_id, $m->name, $m->type, (bool)$m->is_active, $m->created_at, $m->updated_at);
    }
    public function findById(int $id): ?UomCategory { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): UomCategory { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?UomCategory { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
