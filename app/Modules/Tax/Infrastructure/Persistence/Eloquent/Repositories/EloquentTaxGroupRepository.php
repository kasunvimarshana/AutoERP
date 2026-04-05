<?php
declare(strict_types=1);
namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

class EloquentTaxGroupRepository implements TaxGroupRepositoryInterface
{
    public function __construct(private readonly TaxGroupModel $model) {}

    public function findById(int $id): ?TaxGroup
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?TaxGroup
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): TaxGroup
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?TaxGroup
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

    private function toEntity(TaxGroupModel $m): TaxGroup
    {
        return new TaxGroup(
            $m->id, $m->tenant_id, $m->name, $m->code,
            $m->description, (bool) $m->is_active,
            $m->created_at, $m->updated_at,
        );
    }
}
