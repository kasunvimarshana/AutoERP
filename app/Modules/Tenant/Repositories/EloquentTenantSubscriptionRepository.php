<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantCurrentSubscriptionModel;
use Modules\Tenant\Models\TenantSubscriptionModel;

final class EloquentTenantSubscriptionRepository implements TenantSubscriptionRepositoryInterface
{
    public function __construct(
        private readonly TenantSubscriptionModel $subscriptions,
        private readonly TenantCurrentSubscriptionModel $current,
    ) {}

    public function findCurrentByTenant(int $tenantId): ?DataRecord
    {
        $pointer = $this->current->newQuery()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->first();

        $subscription = $pointer?->subscription;

        return $subscription instanceof TenantSubscriptionModel ? $this->record($subscription) : null;
    }

    public function findById(int|string $id): ?DataRecord
    {
        $model = $this->subscriptions->newQuery()->with($this->subscriptionRelations())->find($id);

        return $model instanceof TenantSubscriptionModel ? $this->record($model) : null;
    }

    public function replaceCurrent(int $tenantId, array $attributes, ?int $actorId): DataRecord
    {
        $pointer = $this->current->newQuery()->where('tenant_id', $tenantId)->lockForUpdate()->first();
        if ($pointer instanceof TenantCurrentSubscriptionModel) {
            $this->subscriptions->newQuery()
                ->whereKey($pointer->getAttribute('tenant_subscription_id'))
                ->whereIn('status', ['trial', 'active'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Replaced by a newer subscription.',
                    'updated_by' => $actorId,
                    'row_version' => \Illuminate\Support\Facades\DB::raw('row_version + 1'),
                    'updated_at' => now(),
                ]);
        }

        $subscription = $this->subscriptions->newQuery()->create([
            ...$attributes,
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $this->current->newQuery()->updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'tenant_subscription_id' => $subscription->getKey(),
                'assigned_at' => now(),
                'assigned_by' => $actorId,
            ],
        );

        return $this->findById($subscription->getKey())
            ?? throw new ModelNotFoundException('Created tenant subscription could not be reloaded.');
    }

    public function listExpiredCurrent(DateTimeInterface $now, int $limit): array
    {
        return $this->subscriptions->newQuery()
            ->with($this->subscriptionRelations())
            ->whereHas('currentAssignment')
            ->whereIn('status', ['trial', 'active'])
            ->where(function ($query) use ($now): void {
                $query->where(function ($trial) use ($now): void {
                    $trial->where('status', 'trial')
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', $now);
                })->orWhere(function ($active) use ($now): void {
                    $active->where('status', 'active')
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<=', $now);
                });
            })
            ->orderByRaw('COALESCE(ends_at, trial_ends_at)')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (TenantSubscriptionModel $model): DataRecord => $this->record($model))
            ->all();
    }

    public function expireWithVersion(int|string $id, int $expectedVersion): bool
    {
        return $this->subscriptions->newQuery()
            ->whereKey($id)
            ->where('row_version', $expectedVersion)
            ->whereIn('status', ['trial', 'active'])
            ->update([
                'status' => 'expired',
                'row_version' => $expectedVersion + 1,
                'updated_at' => now(),
            ]) === 1;
    }

    /** @return array<string, mixed> */
    private function relations(): array
    {
        return ['subscription' => fn ($query) => $query->with($this->subscriptionRelations())];
    }

    /** @return list<string> */
    private function subscriptionRelations(): array
    {
        return [
            'revision.plan:id,name,slug,is_active',
            'revision.currency:id,code,name,symbol,is_active',
            'tenant:id,code,name,status',
        ];
    }

    private function record(TenantSubscriptionModel $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['revision'] = $model->relationLoaded('revision') && $model->revision !== null
            ? $model->revision->toArray()
            : null;
        if (is_array($payload['revision'] ?? null)) {
            $payload['revision']['plan'] = $model->revision?->plan?->only(['id', 'name', 'slug', 'is_active']);
            $payload['revision']['currency'] = $model->revision?->currency?->only(['id', 'code', 'name', 'symbol', 'is_active']);
        }
        $payload['tenant'] = $model->relationLoaded('tenant') && $model->tenant !== null
            ? $model->tenant->only(['id', 'code', 'name', 'status'])
            : null;

        return new DataRecord($payload);
    }
}
