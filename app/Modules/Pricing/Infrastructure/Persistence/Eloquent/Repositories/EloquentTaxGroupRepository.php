<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\TaxGroup;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

class EloquentTaxGroupRepository extends EloquentRepository implements TaxGroupRepositoryInterface
{
    public function __construct(TaxGroupModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?TaxGroup
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?TaxGroup
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): TaxGroup
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(TaxGroup $group, array $data): TaxGroup
    {
        $m = $this->model->findOrFail($group->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(TaxGroup $group): bool
    {
        return parent::delete($this->model->findOrFail($group->id));
    }

    private function toEntity(object $m): TaxGroup
    {
        return new TaxGroup(
            id: $m->id,
            tenantId: $m->tenant_id,
            name: $m->name,
            code: $m->code,
            isActive: (bool) $m->is_active,
            description: $m->description,
        );
    }
}
