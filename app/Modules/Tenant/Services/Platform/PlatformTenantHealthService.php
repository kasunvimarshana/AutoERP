<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantCurrentSubscriptionModel;
use Modules\Tenant\Models\TenantDocumentModel;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantEventOutboxModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Models\TenantStorageCleanupJobModel;
use Modules\Tenant\Services\Events\TenantEventOutboxService;
use Modules\Tenant\Services\Storage\TenantStorageCleanupService;
use Modules\Tenant\Services\Storage\TenantStorageReconciliationService;
use Modules\Tenant\Services\Domains\TenantDomainOperationalRetryService;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionReadinessService;

final class PlatformTenantHealthService
{
    public function __construct(
        private readonly TenantModel $tenants,
        private readonly TenantOnboardingStateModel $onboarding,
        private readonly TenantDomainModel $domains,
        private readonly TenantCurrentSubscriptionModel $subscriptions,
        private readonly TenantDocumentModel $documents,
        private readonly TenantStorageCleanupJobModel $cleanupJobs,
        private readonly TenantEventOutboxModel $outboxRows,
        private readonly TenantEventOutboxService $outbox,
        private readonly TenantStorageCleanupService $storageCleanup,
        private readonly TenantStorageReconciliationService $storageReconciliation,
        private readonly TenantDomainOperationalRetryService $domainRetries,
        private readonly TenantSubscriptionReadinessService $subscriptionReadiness,
        private readonly PlatformOperationalInfrastructureHealthService $operationalInfrastructure,
        private readonly TenantInfrastructureCapabilityService $infrastructureCapabilities,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        return $this->executionContext->runAsControlPlane(function (): array {
            $onboardingFailures = $this->onboarding->newQuery()->where('status', TenantOnboardingStatus::FAILED)->count();
            $domainFailures = $this->domains->newQuery()
                ->where(function ($query): void {
                    $query->where('ownership_status', TenantDomainOwnershipStatus::FAILED)
                        ->orWhere('operational_status', TenantDomainOperationalStatus::FAILED);
                })->count();
            $deadOutbox = $this->outboxRows->newQuery()->where('status', 'dead')->count();
            $deadCleanup = $this->cleanupJobs->newQuery()->where('status', 'dead')->count();
            $infrastructure = $this->infrastructureHealth();

            return [
                'generated_at' => $this->clock->now()->format(DATE_ATOM),
                'release' => [
                    'release_id' => $this->nullableConfig('tenant.release.id'),
                    'commit' => $this->nullableConfig('tenant.release.commit'),
                    'environment' => (string) config('app.env', 'production'),
                    'database_strategy' => (string) config('tenant.infrastructure.database_strategy', 'shared_schema'),
                ],
                'tenants' => $this->countsBy($this->tenants->newQuery(), 'status', TenantStatus::values()),
                'onboarding' => $this->countsBy($this->onboarding->newQuery(), 'status', TenantOnboardingStatus::values()),
                'domains' => [
                    'ownership' => $this->countsBy($this->domains->newQuery(), 'ownership_status', TenantDomainOwnershipStatus::values()),
                    'operational' => $this->countsBy($this->domains->newQuery(), 'operational_status', TenantDomainOperationalStatus::values()),
                ],
                'subscriptions' => $this->countsBy(
                    $this->subscriptions->newQuery(),
                    'state',
                    TenantCurrentSubscriptionState::values(),
                ),
                'operations' => [
                    'outbox' => $this->countsBy($this->outboxRows->newQuery(), 'status', ['pending', 'processing', 'published', 'dead']),
                    'storage_cleanup' => $this->countsBy($this->cleanupJobs->newQuery(), 'status', ['pending', 'processing', 'completed', 'dead']),
                ],
                'infrastructure' => $infrastructure,
                'storage' => [
                    'tracked_document_bytes' => (int) $this->documents->newQuery()->sum('size_bytes'),
                    'tracked_document_count' => $this->documents->newQuery()->count(),
                ],
                'alerts' => [
                    'onboarding_failures' => $onboardingFailures,
                    'domain_failures' => $domainFailures,
                    'dead_outbox_events' => $deadOutbox,
                    'dead_storage_cleanup_jobs' => $deadCleanup,
                    'requires_attention' => (
                        $onboardingFailures
                        + $domainFailures
                        + $deadOutbox
                        + $deadCleanup
                    ) > 0
                        || ! $infrastructure['ready'],
                ],
                'failures' => [
                    'onboarding' => $this->failedOnboarding(),
                    'domains' => $this->failedDomains(),
                    'outbox' => $this->deadOutboxEvents(),
                    'storage_cleanup' => $this->deadStorageCleanupJobs(),
                ],
            ];
        });
    }

    /** @return array<string, mixed> */
    public function tenant(int $tenantId): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($tenantId): array {
            $tenant = $this->tenants->newQuery()->whereKey($tenantId)->first();
            if (! $tenant instanceof TenantModel) {
                throw (new ModelNotFoundException())->setModel(TenantModel::class, [$tenantId]);
            }

            $onboarding = $this->onboarding->newQuery()->with('steps')->where('tenant_id', $tenantId)->first();
            $domains = $this->domains->newQuery()
                ->where('tenant_id', $tenantId)
                ->orderBy('domain')
                ->get()
                ->map(static fn (TenantDomainModel $domain): array => [
                    'id' => (int) $domain->getKey(),
                    'domain' => (string) $domain->getAttribute('domain'),
                    'status' => (string) $domain->getAttribute('status'),
                    'ownership_status' => (string) $domain->getAttribute('ownership_status'),
                    'operational_status' => (string) $domain->getAttribute('operational_status'),
                    'last_checked_at' => $domain->getAttribute('last_operational_check_at')?->toAtomString(),
                    'retry_at' => $domain->getAttribute('operational_retry_at')?->toAtomString(),
                    'error_code' => $domain->getAttribute('operational_error_code') ?? $domain->getAttribute('verification_error_code'),
                    'error_message' => $domain->getAttribute('operational_error_message') ?? $domain->getAttribute('verification_error_message'),
                ])->all();
            $currentSubscription = $this->subscriptions->newQuery()
                ->with('subscription')
                ->where('tenant_id', $tenantId)
                ->first();
            $capacity = $this->capacity($tenantId, $currentSubscription);
            $infrastructure = $this->infrastructureHealth();

            return [
                'generated_at' => $this->clock->now()->format(DATE_ATOM),
                'tenant' => [
                    'id' => (int) $tenant->getKey(),
                    'code' => (string) $tenant->getAttribute('code'),
                    'name' => (string) $tenant->getAttribute('name'),
                    'status' => (string) $tenant->getAttribute('status'),
                    'row_version' => (int) $tenant->getAttribute('row_version'),
                ],
                'onboarding' => $onboarding === null ? null : [
                    'status' => (string) $onboarding->getAttribute('status'),
                    'failed_step' => $onboarding->getAttribute('failed_step'),
                    'error_code' => $onboarding->getAttribute('last_error_code'),
                    'error_message' => $onboarding->getAttribute('last_error_message'),
                    'correlation_id' => $onboarding->getAttribute('correlation_id'),
                    'steps' => $onboarding->steps->map(static fn ($step): array => [
                        'step' => (string) $step->getAttribute('step'),
                        'owner_module' => (string) $step->getAttribute('owner_module'),
                        'status' => (string) $step->getAttribute('status'),
                        'attempt_count' => (int) $step->getAttribute('attempt_count'),
                        'error_code' => $step->getAttribute('error_code'),
                        'error_message' => $step->getAttribute('error_message'),
                    ])->all(),
                ],
                'domains' => $domains,
                'subscription' => $currentSubscription === null ? null : [
                    'state' => (string) $currentSubscription->getAttribute('state'),
                    'state_reason' => $currentSubscription->getAttribute('state_reason'),
                    'subscription_id' => (int) $currentSubscription->getAttribute('tenant_subscription_id'),
                    'plan' => $currentSubscription->subscription?->getAttribute('plan_name'),
                    'starts_at' => $currentSubscription->subscription?->getAttribute('starts_at')?->toAtomString(),
                    'ends_at' => $currentSubscription->subscription?->getAttribute('ends_at')?->toAtomString(),
                ],
                'capacity' => $capacity,
                'infrastructure' => $infrastructure,
                'storage' => [
                    'tracked_document_bytes' => (int) $this->documents->newQuery()->where('tenant_id', $tenantId)->sum('size_bytes'),
                    'tracked_document_count' => $this->documents->newQuery()->where('tenant_id', $tenantId)->count(),
                    'reconciliation' => $this->storageReconciliation->reconcile($tenantId),
                    'cleanup' => $this->countsBy(
                        $this->cleanupJobs->newQuery()->where('tenant_id', $tenantId),
                        'status',
                        ['pending', 'processing', 'completed', 'dead'],
                    ),
                ],
                'outbox' => $this->countsBy(
                    $this->outboxRows->newQuery()->where('tenant_id', $tenantId),
                    'status',
                    ['pending', 'processing', 'published', 'dead'],
                ),
            ];
        });
    }

    public function retryDeadOutbox(?string $eventUuid, string $reason): int
    {
        $count = $this->outbox->retryDead($eventUuid);
        $this->recordRecovery('outbox_retry', $eventUuid ?? 'batch', $reason, $count);

        return $count;
    }

    public function retryDeadStorage(?int $jobId, ?int $tenantId, string $reason): int
    {
        $count = $this->storageCleanup->retryDead($jobId, $tenantId);
        $this->recordRecovery('storage_cleanup_retry', (string) ($jobId ?? $tenantId ?? 'batch'), $reason, $count);

        return $count;
    }

    public function retryFailedDomains(?int $tenantId, int $limit, string $reason): int
    {
        $count = $this->domainRetries->queueFailed($tenantId, $limit);
        $this->recordRecovery('domain_operational_retry', (string) ($tenantId ?? 'batch'), $reason, $count);

        return $count;
    }


    /** @return array<string, mixed>|null */
    private function capacity(int $tenantId, ?TenantCurrentSubscriptionModel $current): ?array
    {
        $revisionId = $current?->subscription?->getAttribute('tenant_plan_revision_id');
        if (! is_numeric($revisionId) || (int) $revisionId < 1) {
            return null;
        }

        $assessment = $this->subscriptionReadiness->inspect($tenantId, (int) $revisionId);
        $utilization = [];
        foreach ($assessment['limits'] as $key => $limit) {
            $usage = (int) ($assessment['usage'][$key] ?? 0);
            $utilization[$key] = $limit > 0
                ? round(($usage / $limit) * 100, 2)
                : null;
        }

        return [
            'measured_at' => $this->clock->now()->format(DATE_ATOM),
            'plan_revision_id' => (int) $revisionId,
            'usage' => $assessment['usage'],
            'limits' => $assessment['limits'],
            'utilization_percent' => $utilization,
            'blockers' => $assessment['blockers'],
        ];
    }


    /** @return array<string, mixed> */
    private function infrastructureHealth(): array
    {
        return [
            ...$this->operationalInfrastructure->inspect(),
            'capabilities' => $this->infrastructureCapabilities->inspect(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function failedOnboarding(): array
    {
        return $this->onboarding->newQuery()
            ->join('tenants', 'tenants.id', '=', 'tenant_onboarding_states.tenant_id')
            ->where('tenant_onboarding_states.status', TenantOnboardingStatus::FAILED)
            ->orderByDesc('tenant_onboarding_states.updated_at')
            ->limit(20)
            ->get([
                'tenant_onboarding_states.tenant_id',
                'tenant_onboarding_states.failed_step',
                'tenant_onboarding_states.last_error_code',
                'tenant_onboarding_states.last_error_message',
                'tenant_onboarding_states.correlation_id',
                'tenant_onboarding_states.updated_at',
                'tenants.code as tenant_code',
                'tenants.name as tenant_name',
            ])
            ->map(static fn ($row): array => [
                'tenant_id' => (int) $row->tenant_id,
                'tenant_code' => (string) $row->tenant_code,
                'tenant_name' => (string) $row->tenant_name,
                'failed_step' => $row->failed_step,
                'error_code' => $row->last_error_code,
                'error_message' => $row->last_error_message,
                'correlation_id' => $row->correlation_id,
                'updated_at' => $row->updated_at?->toAtomString(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function failedDomains(): array
    {
        return $this->domains->newQuery()
            ->join('tenants', 'tenants.id', '=', 'tenant_domains.tenant_id')
            ->where(function ($query): void {
                $query->where('tenant_domains.ownership_status', TenantDomainOwnershipStatus::FAILED)
                    ->orWhere('tenant_domains.operational_status', TenantDomainOperationalStatus::FAILED);
            })
            ->orderByDesc('tenant_domains.updated_at')
            ->limit(20)
            ->get([
                'tenant_domains.tenant_id',
                'tenant_domains.domain',
                'tenant_domains.ownership_status',
                'tenant_domains.operational_status',
                'tenant_domains.verification_error_code',
                'tenant_domains.verification_error_message',
                'tenant_domains.operational_error_code',
                'tenant_domains.operational_error_message',
                'tenant_domains.updated_at',
                'tenants.code as tenant_code',
                'tenants.name as tenant_name',
            ])
            ->map(static fn ($row): array => [
                'tenant_id' => (int) $row->tenant_id,
                'tenant_code' => (string) $row->tenant_code,
                'tenant_name' => (string) $row->tenant_name,
                'domain' => (string) $row->domain,
                'ownership_status' => (string) $row->ownership_status,
                'operational_status' => (string) $row->operational_status,
                'error_code' => $row->operational_error_code ?? $row->verification_error_code,
                'error_message' => $row->operational_error_message ?? $row->verification_error_message,
                'updated_at' => $row->updated_at?->toAtomString(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function deadOutboxEvents(): array
    {
        return $this->outboxRows->newQuery()
            ->join('tenants', 'tenants.id', '=', 'tenant_event_outbox.tenant_id')
            ->where('tenant_event_outbox.status', 'dead')
            ->orderByDesc('tenant_event_outbox.dead_at')
            ->limit(20)
            ->get([
                'tenant_event_outbox.event_uuid',
                'tenant_event_outbox.event_type',
                'tenant_event_outbox.tenant_id',
                'tenant_event_outbox.attempts',
                'tenant_event_outbox.last_error_code',
                'tenant_event_outbox.last_error_message',
                'tenant_event_outbox.dead_at',
                'tenants.code as tenant_code',
                'tenants.name as tenant_name',
            ])
            ->map(static fn ($row): array => [
                'event_uuid' => (string) $row->event_uuid,
                'event_type' => (string) $row->event_type,
                'tenant_id' => (int) $row->tenant_id,
                'tenant_code' => (string) $row->tenant_code,
                'tenant_name' => (string) $row->tenant_name,
                'attempts' => (int) $row->attempts,
                'error_code' => $row->last_error_code,
                'error_message' => $row->last_error_message,
                'failed_at' => $row->dead_at?->toAtomString(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function deadStorageCleanupJobs(): array
    {
        return $this->cleanupJobs->newQuery()
            ->join('tenants', 'tenants.id', '=', 'tenant_storage_cleanup_jobs.tenant_id')
            ->where('tenant_storage_cleanup_jobs.status', 'dead')
            ->orderByDesc('tenant_storage_cleanup_jobs.updated_at')
            ->limit(20)
            ->get([
                'tenant_storage_cleanup_jobs.id',
                'tenant_storage_cleanup_jobs.tenant_id',
                'tenant_storage_cleanup_jobs.reason',
                'tenant_storage_cleanup_jobs.attempts',
                'tenant_storage_cleanup_jobs.last_error_code',
                'tenant_storage_cleanup_jobs.last_error_message',
                'tenant_storage_cleanup_jobs.updated_at',
                'tenants.code as tenant_code',
                'tenants.name as tenant_name',
            ])
            ->map(static fn ($row): array => [
                'job_id' => (int) $row->id,
                'tenant_id' => (int) $row->tenant_id,
                'tenant_code' => (string) $row->tenant_code,
                'tenant_name' => (string) $row->tenant_name,
                'reason' => (string) $row->reason,
                'attempts' => (int) $row->attempts,
                'error_code' => $row->last_error_code,
                'error_message' => $row->last_error_message,
                'failed_at' => $row->updated_at?->toAtomString(),
            ])->all();
    }

    /** @param list<string> $expected @return array<string, int> */
    private function countsBy($query, string $column, array $expected): array
    {
        $counts = $query->selectRaw($column.', COUNT(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        foreach ($expected as $value) {
            $counts[$value] ??= 0;
        }
        ksort($counts);

        return $counts;
    }

    private function nullableConfig(string $key): ?string
    {
        $value = trim((string) config($key, ''));

        return $value === '' ? null : $value;
    }

    private function recordRecovery(string $action, string $subjectId, string $reason, int $count): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: 'platform.health.'.$action,
            eventCategory: AuditEventCategory::SYSTEM,
            sourceModule: 'tenant',
            subjectType: 'platform_health_operation',
            subjectId: $subjectId,
            subjectReference: $action,
            changes: ['new' => ['requeued_count' => $count]],
            metadata: ['reason' => trim($reason)],
            tags: ['platform', 'health', 'recovery'],
        ));
    }
}
