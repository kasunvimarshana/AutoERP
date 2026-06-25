<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantPlanRevisionModel;

final class EloquentTenantPlanRepository implements TenantPlanRepositoryInterface
{
    public function __construct(
        private readonly TenantPlanModel $model,
        private readonly ClockInterface $clock,
    ) {}

    public function findById(int|string $id, bool $lockForUpdate = false): ?DataRecord
    {
        $query = $this->query()->whereKey($id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $model = $query->first();

        return $model instanceof TenantPlanModel ? $this->record($model) : null;
    }

    public function findBySlug(string $slug): ?DataRecord
    {
        $model = $this->query()->where('slug', strtolower(trim($slug)))->first();

        return $model instanceof TenantPlanModel ? $this->record($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        $model = $this->model->newQuery()->create($attributes);

        return $this->findById($model->getKey()) ?? $this->record($model);
    }

    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord
    {
        unset($attributes['id'], $attributes['row_version']);
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = $this->clock->now();
        $updated = $this->model->newQuery()
            ->whereKey($id)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findById($id) : null;
    }

    public function pageByFilters(
        ?bool $isActive,
        ?string $billingInterval,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query()
            ->when($isActive !== null, fn (Builder $builder) => $builder->where('is_active', $isActive))
            ->when(
                $billingInterval !== null && trim($billingInterval) !== '',
                fn (Builder $builder) => $builder->whereHas(
                    'currentRevision',
                    fn (Builder $revision) => $revision->where('billing_interval', trim($billingInterval)),
                ),
            )
            ->when($search !== null && trim($search) !== '', function (Builder $builder) use ($search): void {
                $term = trim($search);
                $builder->where(fn (Builder $nested) => $nested
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%"));
            })
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = array_values(array_map(
            fn (Model $model): DataRecord => $this->record($model),
            $paginator->items(),
        ));

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function hasCurrentAssignments(int|string $id): bool
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->whereHas('revisions.subscriptions.currentAssignment', fn (Builder $query) => $query
                ->where('state', TenantCurrentSubscriptionState::ASSIGNED))
            ->exists();
    }

    private function query(): Builder
    {
        return $this->model->newQuery()
            ->with(['currentRevision.currency', 'latestRevision.currency'])
            ->withCount([
                'revisions',
                'subscriptions as total_subscription_count',
                'subscriptions as current_subscription_count' => fn (Builder $query) => $query
                    ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment
                        ->where('state', TenantCurrentSubscriptionState::ASSIGNED)),
                'subscriptions as historical_subscription_count' => fn (Builder $query) => $query
                    ->whereDoesntHave('currentAssignment', fn (Builder $assignment) => $assignment
                        ->where('state', TenantCurrentSubscriptionState::ASSIGNED)),
            ]);
    }

    private function record(TenantPlanModel $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['current_revision'] = $this->revisionPayload(
            $model->relationLoaded('currentRevision') ? $model->currentRevision : null,
        );
        $payload['latest_revision'] = $this->revisionPayload(
            $model->relationLoaded('latestRevision') ? $model->latestRevision : null,
        );

        return new DataRecord($payload);
    }

    /** @return array<string, mixed>|null */
    private function revisionPayload(?TenantPlanRevisionModel $revision): ?array
    {
        if ($revision === null) {
            return null;
        }

        return [
            ...$revision->attributesToArray(),
            'currency' => $revision->currency?->only(['id', 'code', 'name', 'symbol', 'is_active']),
        ];
    }
}
