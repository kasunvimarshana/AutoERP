<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Models\TenantModel;

final class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function __construct(private readonly TenantModel $model) {}

    public function findById(int|string $id): ?DataRecord
    {
        $model = $this->query()->find($id);
        return $model instanceof TenantModel ? $this->record($model) : null;
    }

    public function findByCode(string $code): ?DataRecord
    {
        $model = $this->query()->where('code', strtoupper(trim($code)))->first();
        return $model instanceof TenantModel ? $this->record($model) : null;
    }

    public function findByUuid(string $uuid): ?DataRecord
    {
        $model = $this->query()->where('uuid', trim($uuid))->first();
        return $model instanceof TenantModel ? $this->record($model) : null;
    }

    public function findBySlug(string $slug): ?DataRecord
    {
        $model = $this->query()->where('slug', strtolower(trim($slug)))->first();
        return $model instanceof TenantModel ? $this->record($model) : null;
    }

    public function lockById(int|string $id): ?DataRecord
    {
        $model = $this->query()->whereKey($id)->lockForUpdate()->first();

        return $model instanceof TenantModel ? $this->record($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        return $this->record($this->model->newQuery()->create($attributes));
    }

    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord
    {
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();
        $updated = $this->model->newQuery()
            ->whereKey($id)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findById($id) : null;
    }

    public function pageByFilters(?string $status, ?string $search, int $perPage, int $page): PagedResult
    {
        $query = $this->query()
            ->when($status !== null && trim($status) !== '', fn (Builder $q) => $q->where('status', strtolower(trim($status))))
            ->when($search !== null && trim($search) !== '', function (Builder $q) use ($search): void {
                $term = trim((string) $search);
                $q->where(function (Builder $nested) use ($term): void {
                    $nested->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%");
                });
            })
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = array_values(array_map(
            fn (Model $model): DataRecord => $this->record($model),
            $paginator->items(),
        ));

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    private function query(): Builder
    {
        return $this->model->newQuery()->with([
            'currentSubscription.subscription.revision.plan:id,name,slug,is_active',
            'currentSubscription.subscription.revision.currency:id,code,name,symbol,is_active',
            'baseCurrency:id,code,name,symbol,is_active',
            'onboardingState:tenant_id,status,initial_admin_email,completed_steps,last_error,provisioned_at,completed_at,row_version',
            'primaryDomainAssignment:tenant_id,tenant_domain_id',
            'primaryDomainAssignment.domain:id,tenant_id,domain,status,verified_at',
        ]);
    }

    private function record(TenantModel $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $current = $model->relationLoaded('currentSubscription')
            ? $model->currentSubscription?->subscription
            : null;
        $payload['current_subscription'] = $current?->toArray();
        if (is_array($payload['current_subscription'] ?? null)) {
            $payload['current_subscription']['revision'] = $current?->revision?->toArray();
            if (is_array($payload['current_subscription']['revision'] ?? null)) {
                $payload['current_subscription']['revision']['plan'] = $current?->revision?->plan?->only([
                    'id', 'name', 'slug', 'is_active',
                ]);
                $payload['current_subscription']['revision']['currency'] = $current?->revision?->currency?->only([
                    'id', 'code', 'name', 'symbol', 'is_active',
                ]);
            }
        }
        $payload['base_currency'] = $model->relationLoaded('baseCurrency') && $model->baseCurrency !== null
            ? $model->baseCurrency->only(['id', 'code', 'name', 'symbol', 'is_active'])
            : null;
        $payload['onboarding'] = $model->relationLoaded('onboardingState') && $model->onboardingState !== null
            ? $model->onboardingState->only([
                'status',
                'initial_admin_email',
                'completed_steps',
                'last_error',
                'provisioned_at',
                'completed_at',
                'row_version',
            ])
            : null;
        $primaryDomain = $model->relationLoaded('primaryDomainAssignment')
            ? $model->primaryDomainAssignment?->domain
            : null;
        $payload['primary_domain'] = $primaryDomain?->only([
            'id', 'domain', 'status', 'verified_at',
        ]);
        return new DataRecord($payload);
    }
}
