<?php
declare(strict_types=1);
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
class EloquentUnitOfMeasureRepository implements UnitOfMeasureRepositoryInterface {
    public function __construct(private readonly UnitOfMeasureModel $model) {}
    private function toEntity(UnitOfMeasureModel $m): UnitOfMeasure {
        return new UnitOfMeasure($m->id,$m->tenant_id,$m->category_id,$m->name,$m->symbol,
            (bool)$m->is_base,(float)$m->conversion_factor,$m->type,(bool)$m->is_active,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?UnitOfMeasure { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByCategory(int $categoryId): array {
        return $this->model->newQuery()->where('category_id',$categoryId)->get()->map(fn($m)=>$this->toEntity($m))->all();
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function findBaseUnit(int $categoryId): ?UnitOfMeasure {
        $m=$this->model->newQuery()->where('category_id',$categoryId)->where('is_base',true)->first();
        return $m?$this->toEntity($m):null;
    }
    public function create(array $data): UnitOfMeasure { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?UnitOfMeasure { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
