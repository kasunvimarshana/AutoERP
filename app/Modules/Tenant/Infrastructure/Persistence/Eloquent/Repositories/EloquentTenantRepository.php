<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant
    {
        $model = TenantModel::withoutGlobalScopes()->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByDomain(string $domain): ?Tenant
    {
        $model = TenantModel::withoutGlobalScopes()->where('domain', $domain)->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $model = TenantModel::withoutGlobalScopes()->where('slug', $slug)->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(): array
    {
        return TenantModel::withoutGlobalScopes()
            ->get()
            ->map(fn(TenantModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Tenant $tenant): Tenant
    {
        /** @var TenantModel $model */
        $model = TenantModel::withoutGlobalScopes()->findOrNew($tenant->id);

        $model->fill([
            'name'     => $tenant->name,
            'domain'   => $tenant->domain,
            'slug'     => $tenant->slug,
            'status'   => $tenant->status,
            'plan'     => $tenant->plan,
            'settings' => $tenant->settings,
            'metadata' => $tenant->metadata,
        ]);

        if (! $model->exists) {
            $model->id = $tenant->id;
        }

        $model->save();

        return $this->mapToEntity($model->fresh() ?? $model);
    }

    public function delete(string $id): void
    {
        TenantModel::withoutGlobalScopes()->find($id)?->delete();
    }

    private function mapToEntity(TenantModel $model): Tenant
    {
        return new Tenant(
            id: (string) $model->id,
            name: (string) $model->name,
            domain: (string) $model->domain,
            slug: (string) $model->slug,
            status: (string) $model->status,
            plan: (string) $model->plan,
            settings: (array) ($model->settings ?? []),
            metadata: (array) ($model->metadata ?? []),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
