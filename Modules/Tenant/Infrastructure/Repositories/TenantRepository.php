<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Models\TenantModel;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected function model(): string
    {
        return TenantModel::class;
    }

    public function findById(int $id): ?Tenant
    {
        $model = $this->newQuery()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $model = $this->newQuery()->where('slug', $slug)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByDomain(string $domain): ?Tenant
    {
        $model = $this->newQuery()->where('domain', $domain)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (TenantModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Tenant $tenant): Tenant
    {
        if ($tenant->id !== null) {
            $model = $this->newQuery()->findOrFail($tenant->id);
        } else {
            $model = new TenantModel;
        }

        $model->name = $tenant->name;
        $model->slug = $tenant->slug;
        $model->status = $tenant->status;
        $model->domain = $tenant->domain;
        $model->plan_code = $tenant->planCode;
        $model->currency = $tenant->currency;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        $this->newQuery()->findOrFail($id)->delete();
    }

    private function toDomain(TenantModel $model): Tenant
    {
        return new Tenant(
            id: $model->id,
            name: $model->name,
            slug: $model->slug,
            status: $model->status,
            domain: $model->domain,
            planCode: $model->plan_code,
            currency: $model->currency,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
