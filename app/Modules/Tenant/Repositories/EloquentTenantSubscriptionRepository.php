<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Models\TenantCurrentSubscriptionModel;
use Modules\Tenant\Models\TenantSubscriptionEventModel;
use Modules\Tenant\Models\TenantSubscriptionModel;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPresenter;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;
use RuntimeException;

final class EloquentTenantSubscriptionRepository implements TenantSubscriptionRepositoryInterface
{
    public function __construct(
        private readonly TenantSubscriptionModel $subscriptions,
        private readonly TenantCurrentSubscriptionModel $current,
        private readonly TenantSubscriptionEventModel $events,
        private readonly ClockInterface $clock,
        private readonly TenantSubscriptionPresenter $presenter,
        private readonly CurrencyDirectoryInterface $currencies,
    ) {}

    public function findCurrentByTenant(int $tenantId, bool $lockForUpdate = false): ?DataRecord
    {
        $pointerQuery = $this->current->newQuery()->where('tenant_id', $tenantId);
        if ($lockForUpdate) {
            $pointerQuery->lockForUpdate();
        }
        $pointer = $pointerQuery->first();
        if (! $pointer instanceof TenantCurrentSubscriptionModel) {
            return null;
        }

        $subscriptionQuery = $this->subscriptionQuery()
            ->where('tenant_subscriptions.id', (int) $pointer->getAttribute('tenant_subscription_id'))
            ->where('tenant_subscriptions.tenant_id', $tenantId);
        if ($lockForUpdate) {
            $subscriptionQuery->lockForUpdate();
        }
        $subscription = $subscriptionQuery->first();

        return $subscription instanceof TenantSubscriptionModel
            ? $this->record($subscription, $pointer)
            : null;
    }

    public function findByIdForTenant(int|string $id, int $tenantId, bool $lockForUpdate = false): ?DataRecord
    {
        $query = $this->subscriptionQuery()
            ->where('tenant_subscriptions.id', $id)
            ->where('tenant_subscriptions.tenant_id', $tenantId);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $model = $query->first();

        return $model instanceof TenantSubscriptionModel ? $this->record($model) : null;
    }

    public function pageHistory(int $tenantId, int $perPage, int $page): PagedResult
    {
        $paginator = $this->subscriptionQuery()
            ->where('tenant_subscriptions.tenant_id', $tenantId)
            ->orderByDesc('tenant_subscriptions.revision_number')
            ->paginate(max(1, min($perPage, 100)), ['tenant_subscriptions.*'], 'page', max(1, $page));

        return new PagedResult(
            array_values(array_map(
                fn (Model $model): DataRecord => $this->record($model),
                $paginator->items(),
            )),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function createRevision(int $tenantId, array $attributes, ?int $actorId): DataRecord
    {
        unset($attributes['id'], $attributes['tenant_id'], $attributes['created_by'], $attributes['created_at']);

        $revisionNumber = (int) $this->subscriptions->newQuery()
            ->where('tenant_id', $tenantId)
            ->max('revision_number') + 1;

        $model = $this->subscriptions->newQuery()->create([
            ...$attributes,
            'tenant_id' => $tenantId,
            'revision_number' => $revisionNumber,
            'created_by' => $actorId,
            'created_at' => $this->clock->now(),
        ]);

        return $this->findByIdForTenant($model->getKey(), $tenantId)
            ?? throw new RuntimeException('Created tenant subscription revision could not be reloaded.');
    }

    public function assignCurrent(
        int $tenantId,
        int $subscriptionId,
        ?int $expectedPointerVersion,
        ?int $actorId,
        ?string $reason,
    ): ?DataRecord {
        $pointer = $this->current->newQuery()
            ->where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->first();
        $now = $this->clock->now();

        if ($pointer instanceof TenantCurrentSubscriptionModel) {
            if ($expectedPointerVersion === null || (int) $pointer->getAttribute('row_version') !== $expectedPointerVersion) {
                return null;
            }

            $pointer->forceFill([
                'tenant_subscription_id' => $subscriptionId,
                'state' => TenantCurrentSubscriptionState::ASSIGNED,
                'state_reason' => $reason,
                'state_changed_at' => $now,
                'assigned_at' => $now,
                'assigned_by' => $actorId,
                'row_version' => $expectedPointerVersion + 1,
            ])->save();
        } else {
            if ($expectedPointerVersion !== null) {
                return null;
            }

            $this->current->newQuery()->create([
                'tenant_id' => $tenantId,
                'tenant_subscription_id' => $subscriptionId,
                'state' => TenantCurrentSubscriptionState::ASSIGNED,
                'state_reason' => $reason,
                'state_changed_at' => $now,
                'assigned_at' => $now,
                'assigned_by' => $actorId,
                'row_version' => 1,
            ]);
        }

        return $this->findCurrentByTenant($tenantId);
    }

    public function transitionCurrentState(
        int $tenantId,
        int $expectedPointerVersion,
        string $state,
        ?string $reason,
        ?int $actorId,
    ): ?DataRecord {
        $updated = $this->current->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedPointerVersion)
            ->update([
                'state' => $state,
                'state_reason' => $reason,
                'state_changed_at' => $this->clock->now(),
                'row_version' => $expectedPointerVersion + 1,
                'assigned_by' => $actorId,
                'updated_at' => $this->clock->now(),
            ]);

        return $updated === 1 ? $this->findCurrentByTenant($tenantId) : null;
    }

    public function listExpiredCurrent(DateTimeInterface $now, int $limit): array
    {
        return $this->subscriptionQuery()
            ->join('tenant_current_subscriptions', function ($join): void {
                $join->on('tenant_current_subscriptions.tenant_subscription_id', '=', 'tenant_subscriptions.id')
                    ->on('tenant_current_subscriptions.tenant_id', '=', 'tenant_subscriptions.tenant_id');
            })
            ->where('tenant_current_subscriptions.state', TenantCurrentSubscriptionState::ASSIGNED)
            ->where(function (Builder $query) use ($now): void {
                $query->where(function (Builder $trial) use ($now): void {
                    $trial->where('tenant_subscriptions.contract_status', 'trial')
                        ->whereNotNull('tenant_subscriptions.trial_ends_at')
                        ->where('tenant_subscriptions.trial_ends_at', '<=', $now);
                })->orWhere(function (Builder $active) use ($now): void {
                    $active->where('tenant_subscriptions.contract_status', 'active')
                        ->whereNotNull('tenant_subscriptions.ends_at')
                        ->where('tenant_subscriptions.ends_at', '<=', $now);
                });
            })
            ->orderByRaw('COALESCE(tenant_subscriptions.ends_at, tenant_subscriptions.trial_ends_at)')
            ->limit(max(1, min($limit, 500)))
            ->get(['tenant_subscriptions.*', 'tenant_current_subscriptions.row_version as pointer_row_version'])
            ->map(function (TenantSubscriptionModel $model): DataRecord {
                $pointer = new TenantCurrentSubscriptionModel([
                    'tenant_id' => $model->getAttribute('tenant_id'),
                    'tenant_subscription_id' => $model->getKey(),
                    'state' => TenantCurrentSubscriptionState::ASSIGNED,
                    'row_version' => $model->getAttribute('pointer_row_version'),
                ]);

                return $this->record($model, $pointer);
            })
            ->values()
            ->all();
    }

    public function recordEvent(
        int $tenantId,
        int $subscriptionId,
        ?int $previousSubscriptionId,
        string $eventType,
        ?string $reason,
        array $actor,
        DateTimeInterface $occurredAt,
    ): void {
        $this->events->newQuery()->create([
            'tenant_id' => $tenantId,
            'tenant_subscription_id' => $subscriptionId,
            'previous_subscription_id' => $previousSubscriptionId,
            'event_type' => $eventType,
            'reason' => $reason,
            'actor_id' => $actor['id'],
            'actor_type' => $actor['type'],
            'actor_name' => $actor['name'],
            'actor_email' => $actor['email'],
            'occurred_at' => $occurredAt,
        ]);
    }

    private function subscriptionQuery(): Builder
    {
        return $this->subscriptions->newQuery()->with([
            'revision.plan:id,name,slug,is_active',
            'tenant:id,code,name,status',
        ]);
    }

    private function record(
        TenantSubscriptionModel $model,
        ?TenantCurrentSubscriptionModel $pointer = null,
    ): DataRecord {
        $payload = $model->attributesToArray();
        $payload['revision'] = $model->relationLoaded('revision') && $model->revision !== null
            ? $model->revision->toArray()
            : null;
        if (is_array($payload['revision'] ?? null)) {
            $payload['revision']['plan'] = $model->revision?->plan?->only(['id', 'name', 'slug', 'is_active']);
            $payload['revision']['currency'] = $this->currencies->find(
                is_numeric($payload['revision']['currency_id'] ?? null) ? (int) $payload['revision']['currency_id'] : null,
            );
        }
        $payload['tenant'] = $model->relationLoaded('tenant') && $model->tenant !== null
            ? $model->tenant->only(['id', 'code', 'name', 'status'])
            : null;

        if ($pointer instanceof TenantCurrentSubscriptionModel) {
            $payload['current_state'] = $pointer->getAttribute('state');
            $payload['current_state_reason'] = $pointer->getAttribute('state_reason');
            $payload['current_state_changed_at'] = $pointer->getAttribute('state_changed_at');
            $payload['row_version'] = (int) $pointer->getAttribute('row_version');
            $payload['assigned_at'] = $pointer->getAttribute('assigned_at');
            $payload['assigned_by'] = $pointer->getAttribute('assigned_by');
        }

        return new DataRecord($this->presenter->present($payload));
    }
}
