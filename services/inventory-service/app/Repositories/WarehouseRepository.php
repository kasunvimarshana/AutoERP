<?php
namespace App\Repositories;
use App\Domain\Contracts\WarehouseRepositoryInterface;
use App\Domain\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    protected array $searchableFields = ['name', 'code'];
    public function __construct(Warehouse $model) { parent::__construct($model); }
    protected function getAllowedFilterFields(): array { return ['tenant_id','code','type','is_active']; }
    protected function getAllowedSortFields(): array { return ['name','code','type','created_at','updated_at']; }

    public function findById(string $tenantId, string $id): ?object
    { return $this->model->byTenant($tenantId)->find($id); }

    public function findByCode(string $tenantId, string $code): ?object
    { return $this->model->byTenant($tenantId)->byCode($code)->first(); }

    public function list(string $tenantId, array $params = []): mixed
    { $params['filter']['tenant_id'] = $tenantId; return $this->query($params); }

    public function getActiveWarehouses(string $tenantId): mixed
    { return $this->model->byTenant($tenantId)->active()->get(); }

    public function existsByCode(string $tenantId, string $code, ?string $excludeId = null): bool
    {
        $q = $this->model->byTenant($tenantId)->byCode($code);
        if ($excludeId) $q->where('id', '!=', $excludeId);
        return $q->exists();
    }

    protected function applyDefaultSort(Builder $query): Builder { return $query->orderBy('name'); }
}
