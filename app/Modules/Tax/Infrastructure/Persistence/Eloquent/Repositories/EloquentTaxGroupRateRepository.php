<?php
declare(strict_types=1);
namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupRateModel;

class EloquentTaxGroupRateRepository implements TaxGroupRateRepositoryInterface
{
    public function __construct(private readonly TaxGroupRateModel $model) {}

    public function findById(int $id): ?TaxGroupRate
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTaxGroup(int $taxGroupId): array
    {
        return $this->model->newQuery()
            ->where('tax_group_id', $taxGroupId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): TaxGroupRate
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?TaxGroupRate
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    public function deleteByTaxGroup(int $taxGroupId): void
    {
        $this->model->newQuery()->where('tax_group_id', $taxGroupId)->delete();
    }

    private function toEntity(TaxGroupRateModel $m): TaxGroupRate
    {
        return new TaxGroupRate(
            $m->id, $m->tenant_id, $m->tax_group_id,
            $m->tax_rate_code, $m->tax_rate_name,
            (float) $m->rate, (int) $m->sort_order, (bool) $m->is_compound,
            $m->created_at, $m->updated_at,
        );
    }
}
