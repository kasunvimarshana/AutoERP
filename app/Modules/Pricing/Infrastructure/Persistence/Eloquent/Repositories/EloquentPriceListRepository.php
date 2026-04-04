<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

class EloquentPriceListRepository extends EloquentRepository implements PriceListRepositoryInterface
{
    public function __construct(PriceListModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?PriceList
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?PriceList
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findDefault(int $tenantId): ?PriceList
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('is_default', true)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): PriceList
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(PriceList $priceList, array $data): PriceList
    {
        $m = $this->model->findOrFail($priceList->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(PriceList $priceList): bool
    {
        return parent::delete($this->model->findOrFail($priceList->id));
    }

    private function toEntity(object $m): PriceList
    {
        return new PriceList(
            id: $m->id,
            tenantId: $m->tenant_id,
            name: $m->name,
            code: $m->code,
            currency: $m->currency,
            isDefault: (bool) $m->is_default,
            isActive: (bool) $m->is_active,
            validFrom: $m->valid_from?->toDateString(),
            validTo: $m->valid_to?->toDateString(),
            description: $m->description,
        );
    }
}
