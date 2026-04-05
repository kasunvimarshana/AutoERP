<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Pricing\Domain\Entities\TaxRate;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
class EloquentTaxRateRepository implements TaxRateRepositoryInterface {
    public function __construct(private readonly TaxRateModel $model) {}
    private function toEntity(TaxRateModel $m): TaxRate {
        return new TaxRate($m->id,$m->tenant_id,$m->name,$m->code,(float)$m->rate,$m->type,(bool)$m->is_compound,(bool)$m->is_active,$m->applies_to,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?TaxRate { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): TaxRate { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?TaxRate { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
