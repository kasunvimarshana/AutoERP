<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantPlanRevisionModel;

final class EloquentTenantPlanRevisionRepository implements TenantPlanRevisionRepositoryInterface
{
    public function __construct(
        private readonly TenantPlanModel $plans,
        private readonly TenantPlanRevisionModel $revisions,
        private readonly ClockInterface $clock,
    ) {}

    public function findById(int|string $id, bool $lockForUpdate = false): ?DataRecord
    {
        $query = $this->revisions->newQuery()
            ->with([
                'plan' => function ($query) use ($lockForUpdate): void {
                    $query->select(['id', 'name', 'slug', 'is_active']);
                    if ($lockForUpdate) {
                        $query->lockForUpdate();
                    }
                },
                'currency' => function ($query) use ($lockForUpdate): void {
                    $query->select(['id', 'code', 'name', 'symbol', 'is_active']);
                    if ($lockForUpdate) {
                        $query->lockForUpdate();
                    }
                },
            ])
            ->whereKey($id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $model = $query->first();

        return $model instanceof TenantPlanRevisionModel ? $this->record($model) : null;
    }

    public function findLatestByPlan(int|string $planId): ?DataRecord
    {
        $model = $this->revisions->newQuery()
            ->with(['plan:id,name,slug,is_active', 'currency:id,code,name,symbol,is_active'])
            ->where('tenant_plan_id', $planId)
            ->orderByDesc('revision_number')
            ->first();

        return $model instanceof TenantPlanRevisionModel ? $this->record($model) : null;
    }

    public function listByPlan(int|string $planId): array
    {
        return $this->revisions->newQuery()
            ->with(['plan:id,name,slug,is_active', 'currency:id,code,name,symbol,is_active'])
            ->withCount([
                'subscriptions as total_subscription_count',
                'subscriptions as current_subscription_count' => fn (Builder $query) => $query
                    ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment
                        ->where('state', TenantCurrentSubscriptionState::ASSIGNED)),
                'subscriptions as historical_subscription_count' => fn (Builder $query) => $query
                    ->whereDoesntHave('currentAssignment', fn (Builder $assignment) => $assignment
                        ->where('state', TenantCurrentSubscriptionState::ASSIGNED)),
            ])
            ->where('tenant_plan_id', $planId)
            ->orderByDesc('revision_number')
            ->get()
            ->map(fn (TenantPlanRevisionModel $model): DataRecord => $this->record($model))
            ->all();
    }

    public function createNext(int|string $planId, array $attributes): DataRecord
    {
        $plan = $this->plans->newQuery()->whereKey($planId)->lockForUpdate()->first();
        if (! $plan instanceof TenantPlanModel) {
            throw (new ModelNotFoundException)->setModel(TenantPlanModel::class, [$planId]);
        }

        $lastNumber = (int) $this->revisions->newQuery()
            ->where('tenant_plan_id', $planId)
            ->max('revision_number');

        $model = $this->revisions->newQuery()->create([
            ...$attributes,
            'tenant_plan_id' => (int) $planId,
            'revision_number' => $lastNumber + 1,
            'created_at' => $attributes['created_at'] ?? $this->clock->now(),
        ]);

        return $this->findById($model->getKey())
            ?? throw new ModelNotFoundException('Created tenant plan revision could not be reloaded.');
    }

    private function record(TenantPlanRevisionModel $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['plan'] = $model->relationLoaded('plan') && $model->plan !== null
            ? $model->plan->only(['id', 'name', 'slug', 'is_active'])
            : null;
        $payload['currency'] = $model->relationLoaded('currency') && $model->currency !== null
            ? $model->currency->only(['id', 'code', 'name', 'symbol', 'is_active'])
            : null;

        return new DataRecord($payload);
    }
}
