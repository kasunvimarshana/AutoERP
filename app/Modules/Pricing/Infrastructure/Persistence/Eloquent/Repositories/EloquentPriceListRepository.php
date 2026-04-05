<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
class EloquentPriceListRepository implements PriceListRepositoryInterface {
    public function __construct(private readonly PriceListModel $model) {}
    private function toEntity(PriceListModel $m): PriceList {
        return new PriceList($m->id,$m->tenant_id,$m->name,$m->currency,(float)$m->discount_percent,
            (bool)$m->is_default,(bool)$m->is_active,$m->valid_from?->format('Y-m-d'),$m->valid_to?->format('Y-m-d'),$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?PriceList { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): PriceList { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?PriceList { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
