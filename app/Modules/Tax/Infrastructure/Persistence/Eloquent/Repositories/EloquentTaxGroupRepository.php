<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

class EloquentTaxGroupRepository implements TaxGroupRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TaxGroup
    {
        $model = TaxGroupModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByCode(string $tenantId, string $code): ?TaxGroup
    {
        $model = TaxGroupModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return TaxGroupModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(TaxGroupModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(TaxGroup $group): void
    {
        /** @var TaxGroupModel $model */
        $model = TaxGroupModel::withoutGlobalScopes()->findOrNew($group->id);

        $model->fill([
            'tenant_id'   => $group->tenantId,
            'name'        => $group->name,
            'code'        => $group->code,
            'description' => $group->description,
            'is_compound' => $group->isCompound,
            'is_active'   => $group->isActive,
        ]);

        if (! $model->exists) {
            $model->id = $group->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        TaxGroupModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(TaxGroupModel $model): TaxGroup
    {
        return new TaxGroup(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            name: (string) $model->name,
            code: (string) $model->code,
            description: $model->description !== null ? (string) $model->description : null,
            isCompound: (bool) $model->is_compound,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
