<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantPlanRevisionModel;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;

final class EloquentTenantPlanRepository implements TenantPlanRepositoryInterface
{
    public function __construct(
        private readonly TenantPlanModel $model,
        private readonly ClockInterface $clock,
        private readonly CurrencyDirectoryInterface $currencies,
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
            ->with(['currentRevision', 'latestRevision'])
            ->withCount([
                'revisions',
                'subscriptions as total_subscription_count',
                'subscriptions as assigned_subscription_count' => fn (Builder $query) => $query
                    ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment
                        ->where('state', TenantCurrentSubscriptionState::ASSIGNED)),
                'subscriptions as current_subscription_count' => function (Builder $query): void {
                    $now = $this->clock->now();
                    $query->where('starts_at', '<=', $now)
                        ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment
                            ->where('state', TenantCurrentSubscriptionState::ASSIGNED))
                        ->where(function (Builder $effective) use ($now): void {
                            $effective->where(function (Builder $trial) use ($now): void {
                                $trial->where('contract_status', TenantSubscriptionStatus::TRIAL)
                                    ->where('trial_ends_at', '>', $now);
                            })->orWhere(function (Builder $active) use ($now): void {
                                $active->where('contract_status', TenantSubscriptionStatus::ACTIVE)
                                    ->where(function (Builder $end) use ($now): void {
                                        $end->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                                    });
                            });
                        });
                },
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

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array<string, mixed>|null */
    private function revisionPayload(?TenantPlanRevisionModel $revision): ?array
    {
        if ($revision === null) {
            return null;
        }

        return [
            ...$revision->attributesToArray(),
            'currency' => $this->currencies->find($this->positiveInt($revision->getAttribute('currency_id'))),
        ];
    }
}
