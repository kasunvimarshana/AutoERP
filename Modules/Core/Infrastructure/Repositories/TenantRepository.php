<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Repositories;

use DateTimeImmutable;
use Modules\Core\Domain\Contracts\TenantRepositoryInterface;
use Modules\Core\Domain\Entities\Tenant as TenantEntity;
use Modules\Core\Domain\ValueObjects\TenantId;
use Modules\Core\Infrastructure\Models\Tenant as TenantModel;

class TenantRepository implements TenantRepositoryInterface
{
    public function findById(TenantId $id): ?TenantEntity
    {
        $model = TenantModel::find($id->getValue());

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(string $slug): ?TenantEntity
    {
        $model = TenantModel::where('slug', $slug)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByDomain(string $domain): ?TenantEntity
    {
        $model = TenantModel::where('domain', $domain)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return TenantModel::all()
            ->map(fn (TenantModel $m): TenantEntity => $this->toDomain($m))
            ->all();
    }

    public function save(TenantEntity $tenant): TenantEntity
    {
        $model = TenantModel::updateOrCreate(
            ['id' => $tenant->getId()->getValue()],
            [
                'name'      => $tenant->getName(),
                'slug'      => $tenant->getSlug(),
                'domain'    => $tenant->getDomain(),
                'plan'      => $tenant->getPlan(),
                'is_active' => $tenant->isActive(),
            ]
        );

        return $this->toDomain($model);
    }

    public function delete(TenantId $id): void
    {
        TenantModel::find($id->getValue())?->delete();
    }

    private function toDomain(TenantModel $model): TenantEntity
    {
        return new TenantEntity(
            id: new TenantId((int) $model->id),
            name: (string) $model->name,
            slug: (string) $model->slug,
            domain: $model->domain,
            plan: (string) $model->plan,
            isActive: (bool) $model->is_active,
            createdAt: new DateTimeImmutable((string) $model->created_at),
        );
    }
}
