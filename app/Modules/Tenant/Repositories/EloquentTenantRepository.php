<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Data\TenantDirectoryFilters;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPresenter;
use Modules\ReferenceData\Contracts\CurrencyDirectoryInterface;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;

final class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function __construct(
        private readonly TenantModel $model,
        private readonly ClockInterface $clock,
        private readonly TenantSubscriptionPresenter $subscriptionPresenter,
        private readonly CurrencyDirectoryInterface $currencies,
    ) {}

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
        $attributes['updated_at'] = $this->clock->now();
        $updated = $this->model->newQuery()
            ->whereKey($id)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findById($id) : null;
    }

    public function pageByFilters(TenantDirectoryFilters $filters): PagedResult
    {
        $query = $this->query()
            ->when($filters->status !== null, fn (Builder $query) => $query
                ->where('status', strtolower($filters->status)))
            ->when($filters->search !== null, function (Builder $query) use ($filters): void {
                $term = $filters->search;
                $query->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhereHas('domains', fn (Builder $domainQuery) => $domainQuery
                            ->where('domain', 'like', "%{$term}%"));
                });
            })
            ->when($filters->onboardingStatus !== null, fn (Builder $query) => $query
                ->whereHas('onboardingState', fn (Builder $stateQuery) => $stateQuery
                    ->where('status', $filters->onboardingStatus)))
            ->when($filters->domainOperationalStatus !== null, fn (Builder $query) => $query
                ->whereHas('primaryDomainAssignment.domain', fn (Builder $domainQuery) => $domainQuery
                    ->where('operational_status', $filters->domainOperationalStatus)))
            ->when($filters->subscriptionState !== null, fn (Builder $query) => $query
                ->whereHas('currentSubscription', fn (Builder $subscriptionQuery) => $subscriptionQuery
                    ->where('state', $filters->subscriptionState)))
            ->when($filters->subscriptionEffectiveStatus !== null, function (Builder $query) use ($filters): void {
                $this->applyEffectiveSubscriptionStatus($query, $filters->subscriptionEffectiveStatus);
            })
            ->when($filters->planId !== null, fn (Builder $query) => $query
                ->whereHas('currentSubscription.subscription.revision', fn (Builder $revisionQuery) => $revisionQuery
                    ->where('tenant_plan_id', $filters->planId)))
            ->when($filters->expiresWithinDays !== null, function (Builder $query) use ($filters): void {
                $now = $this->clock->now();
                $until = $now->modify(sprintf('+%d days', $filters->expiresWithinDays));
                $query->whereHas('currentSubscription', function (Builder $pointerQuery) use ($now, $until): void {
                    $pointerQuery->where('state', TenantCurrentSubscriptionState::ASSIGNED)
                        ->whereHas('subscription', function (Builder $subscriptionQuery) use ($now, $until): void {
                            $subscriptionQuery->where(function (Builder $expiryQuery) use ($now, $until): void {
                                $expiryQuery->where(function (Builder $trialQuery) use ($now, $until): void {
                                    $trialQuery->where('contract_status', TenantSubscriptionStatus::TRIAL)
                                        ->whereBetween('trial_ends_at', [$now, $until]);
                                })->orWhere(function (Builder $activeQuery) use ($now, $until): void {
                                    $activeQuery->where('contract_status', TenantSubscriptionStatus::ACTIVE)
                                        ->whereBetween('ends_at', [$now, $until]);
                                });
                            });
                        });
                });
            })
            ->orderBy('name');

        $paginator = $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
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


    private function applyEffectiveSubscriptionStatus(Builder $query, string $status): void
    {
        $now = $this->clock->now();

        if ($status === TenantSubscriptionStatus::CANCELLED) {
            $query->whereHas('currentSubscription', fn (Builder $pointer) => $pointer
                ->where('state', TenantCurrentSubscriptionState::CANCELLED));
            return;
        }

        if ($status === TenantSubscriptionStatus::EXPIRED) {
            $query->whereHas('currentSubscription', function (Builder $pointer) use ($now): void {
                $pointer->where(function (Builder $state) use ($now): void {
                    $state->where('state', TenantCurrentSubscriptionState::EXPIRED)
                        ->orWhere(function (Builder $assigned) use ($now): void {
                            $assigned->where('state', TenantCurrentSubscriptionState::ASSIGNED)
                                ->whereHas('subscription', function (Builder $subscription) use ($now): void {
                                    $subscription->where(function (Builder $expired) use ($now): void {
                                        $expired->where(function (Builder $trial) use ($now): void {
                                            $trial->where('contract_status', TenantSubscriptionStatus::TRIAL)
                                                ->where('trial_ends_at', '<=', $now);
                                        })->orWhere(function (Builder $active) use ($now): void {
                                            $active->where('contract_status', TenantSubscriptionStatus::ACTIVE)
                                                ->whereNotNull('ends_at')
                                                ->where('ends_at', '<=', $now);
                                        });
                                    });
                                });
                        });
                });
            });
            return;
        }

        $query->whereHas('currentSubscription', function (Builder $pointer) use ($now, $status): void {
            $pointer->where('state', TenantCurrentSubscriptionState::ASSIGNED)
                ->whereHas('subscription', function (Builder $subscription) use ($now, $status): void {
                    if ($status === TenantSubscriptionPolicy::SCHEDULED) {
                        $subscription->where('starts_at', '>', $now);
                        return;
                    }
                    if ($status === TenantSubscriptionStatus::TRIAL) {
                        $subscription->where('contract_status', TenantSubscriptionStatus::TRIAL)
                            ->where('starts_at', '<=', $now)
                            ->where('trial_ends_at', '>', $now);
                        return;
                    }
                    if ($status === TenantSubscriptionStatus::ACTIVE) {
                        $subscription->where('contract_status', TenantSubscriptionStatus::ACTIVE)
                            ->where('starts_at', '<=', $now)
                            ->where(function (Builder $end) use ($now): void {
                                $end->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                            });
                    }
                });
        });
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function query(): Builder
    {
        return $this->model->newQuery()->with([
            'currentSubscription:tenant_id,tenant_subscription_id,state,state_reason,state_changed_at,row_version,assigned_at,assigned_by',
            'currentSubscription.subscription.revision.plan:id,name,slug,is_active',
            'onboardingState:tenant_id,status,operation_id,initial_admin_email,root_organization_unit_id,super_admin_role_id,invitation_id,failed_step,last_error_code,last_error_message,correlation_id,provisioned_at,completed_at,row_version',
            'onboardingState.steps:id,tenant_id,step,status,attempt_count,owner_module,started_at,completed_at,error_code,error_message,correlation_id',
            'primaryDomainAssignment:tenant_id,tenant_domain_id',
            'primaryDomainAssignment.domain:id,tenant_id,domain,status,ownership_status,routing_status,tls_status,reachability_status,operational_status,verified_at,last_operational_check_at,tls_expires_at',
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
            $pointer = $model->currentSubscription;
            $payload['current_subscription']['current_state'] = $pointer?->getAttribute('state');
            $payload['current_subscription']['current_state_reason'] = $pointer?->getAttribute('state_reason');
            $payload['current_subscription']['current_state_changed_at'] = $pointer?->getAttribute('state_changed_at');
            $payload['current_subscription']['row_version'] = $pointer?->getAttribute('row_version');
            $payload['current_subscription']['assigned_at'] = $pointer?->getAttribute('assigned_at');
            $payload['current_subscription']['assigned_by'] = $pointer?->getAttribute('assigned_by');
            $payload['current_subscription']['revision'] = $current?->revision?->toArray();
            if (is_array($payload['current_subscription']['revision'] ?? null)) {
                $payload['current_subscription']['revision']['plan'] = $current?->revision?->plan?->only([
                    'id', 'name', 'slug', 'is_active',
                ]);
                $payload['current_subscription']['revision']['currency'] = $this->currencies->find(
                    $this->positiveInt($payload['current_subscription']['revision']['currency_id'] ?? null),
                );
            }
            $payload['current_subscription'] = $this->subscriptionPresenter->present(
                $payload['current_subscription'],
            );
        }
        $payload['base_currency'] = $this->currencies->find(
            $this->positiveInt($payload['base_currency_id'] ?? null),
        );
        $payload['onboarding'] = null;
        if ($model->relationLoaded('onboardingState') && $model->onboardingState !== null) {
            $payload['onboarding'] = $model->onboardingState->only([
                'status',
                'initial_admin_email',
                'operation_id',
                'root_organization_unit_id',
                'super_admin_role_id',
                'invitation_id',
                'failed_step',
                'last_error_code',
                'last_error_message',
                'correlation_id',
                'provisioned_at',
                'completed_at',
                'row_version',
            ]);
            $payload['onboarding']['steps'] = $model->onboardingState->relationLoaded('steps')
                ? $model->onboardingState->steps->toArray()
                : [];
            $payload['onboarding']['completed_steps'] = array_values(array_map(
                static fn (array $step): string => (string) $step['step'],
                array_filter(
                    $payload['onboarding']['steps'],
                    static fn (array $step): bool => ($step['status'] ?? null) === 'completed',
                ),
            ));
        }
        $primaryDomain = $model->relationLoaded('primaryDomainAssignment')
            ? $model->primaryDomainAssignment?->domain
            : null;
        $payload['primary_domain'] = $primaryDomain?->only([
            'id', 'domain', 'status', 'ownership_status', 'routing_status', 'tls_status',
            'reachability_status', 'operational_status', 'verified_at',
            'last_operational_check_at', 'tls_expires_at',
        ]);
        return new DataRecord($payload);
    }
}
