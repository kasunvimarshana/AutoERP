<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\TaxRate;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;

class EloquentTaxRateRepository extends EloquentRepository implements TaxRateRepositoryInterface
{
    public function __construct(TaxRateModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?TaxRate
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?TaxRate
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): TaxRate
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(TaxRate $rate, array $data): TaxRate
    {
        $m = $this->model->findOrFail($rate->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(TaxRate $rate): bool
    {
        return parent::delete($this->model->findOrFail($rate->id));
    }

    private function toEntity(object $m): TaxRate
    {
        return new TaxRate(
            id: $m->id,
            tenantId: $m->tenant_id,
            name: $m->name,
            code: $m->code,
            rate: (float) $m->rate,
            type: $m->type,
            isActive: (bool) $m->is_active,
            description: $m->description,
            region: $m->region,
            taxClass: $m->tax_class,
        );
    }
}
