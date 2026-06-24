<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantPlanRevisionModel;

final class EloquentTenantPlanRevisionRepository implements TenantPlanRevisionRepositoryInterface
{
    public function __construct(
        private readonly TenantPlanModel $plans,
        private readonly TenantPlanRevisionModel $revisions,
    ) {}

    public function findById(int|string $id): ?DataRecord
    {
        $model = $this->revisions->newQuery()
            ->with(['plan:id,name,slug,is_active', 'currency:id,code,name,symbol,is_active'])
            ->find($id);

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
            'created_at' => now(),
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
