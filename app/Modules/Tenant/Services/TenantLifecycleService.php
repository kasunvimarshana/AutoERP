<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantLifecycleEventModel;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Events\TenantEventOutboxService;
use Modules\Tenant\Services\Onboarding\TenantReadinessService;
use RuntimeException;

final class TenantLifecycleService
{
    private const OUTCOME_SUCCESS = 'success';
    private const OUTCOME_NOT_FOUND = 'not_found';
    private const OUTCOME_VERSION_CONFLICT = 'version_conflict';
    private const OUTCOME_INVALID_TRANSITION = 'invalid_transition';
    private const OUTCOME_READINESS_BLOCKED = 'readiness_blocked';

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantReadinessService $readiness,
        private readonly TenantOnboardingStateModel $onboardingStates,
        private readonly TenantLifecycleEventModel $lifecycleEvents,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantActorSnapshotFactory $actorSnapshots,
        private readonly AuditRecorderInterface $audit,
        private readonly TenantEventOutboxService $outbox,
        private readonly ClockInterface $clock,
        private readonly TransactionManagerInterface $transactions,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function transition(
        int|string $id,
        int $expectedVersion,
        string $targetStatus,
        ?string $reason = null,
    ): Result {
        $tenant = $this->tenants->findById($id);
        if ($tenant === null) {
            return $this->notFound();
        }

        $current = (string) $tenant->require('status');
        if (! TenantLifecycleState::canTransition($current, $targetStatus)) {
            return $this->invalidTransition($current, $targetStatus);
        }
        if ($expectedVersion < 1 || (int) $tenant->require('row_version') !== $expectedVersion) {
            return $this->versionConflict();
        }

        if ($targetStatus === TenantStatus::ACTIVE) {
            $preflight = $this->readiness->inspect((int) $tenant->id());
            if (! $preflight['ready']) {
                return $this->readinessFailure($preflight);
            }
        }

        $normalizedReason = $reason === null || trim($reason) === '' ? null : trim($reason);

        /**
         * @var array{
         *   status:string,
         *   tenant?:DataRecord,
         *   current_status?:string,
         *   readiness?:array<string,mixed>
         * } $outcome
         */
        $outcome = $this->transactions->runInTransaction(function () use (
            $id,
            $expectedVersion,
            $targetStatus,
            $normalizedReason,
        ): array {
            $locked = $this->tenants->lockById($id);
            if ($locked === null) {
                return ['status' => self::OUTCOME_NOT_FOUND];
            }
            if ((int) $locked->require('row_version') !== $expectedVersion) {
                return ['status' => self::OUTCOME_VERSION_CONFLICT];
            }

            $currentStatus = (string) $locked->require('status');
            if (! TenantLifecycleState::canTransition($currentStatus, $targetStatus)) {
                return [
                    'status' => self::OUTCOME_INVALID_TRANSITION,
                    'current_status' => $currentStatus,
                ];
            }

            $tenantId = (int) $locked->id();
            if ($targetStatus === TenantStatus::ACTIVE) {
                // Domain, subscription, and onboarding readiness may change after the
                // preflight response. Activation therefore rechecks every invariant
                // while the tenant row is locked.
                $readiness = $this->readiness->inspect($tenantId, true);
                if (! $readiness['ready']) {
                    return [
                        'status' => self::OUTCOME_READINESS_BLOCKED,
                        'readiness' => $readiness,
                    ];
                }
            }

            $now = $this->clock->now();
            $attributes = [
                'status' => $targetStatus,
                'status_reason' => $normalizedReason,
                'status_changed_at' => $now,
                'activated_at' => $targetStatus === TenantStatus::ACTIVE
                    ? ($locked->get('activated_at') ?? $now)
                    : $locked->get('activated_at'),
                'suspended_at' => $targetStatus === TenantStatus::SUSPENDED
                    ? $now
                    : $locked->get('suspended_at'),
                'archived_at' => $targetStatus === TenantStatus::ARCHIVED
                    ? $now
                    : $locked->get('archived_at'),
                'updated_by' => $this->currentUser->currentUserId(),
            ];

            $updated = $this->tenants->updateWithVersion($id, $expectedVersion, $attributes);
            if ($updated === null) {
                throw new RuntimeException('Locked tenant could not be versioned during lifecycle transition.');
            }

            if ($targetStatus === TenantStatus::ACTIVE) {
                $this->executionContext->runForTenant($tenantId, function () use ($tenantId): void {
                    $state = $this->onboardingStates->newQuery()
                        ->where('tenant_id', $tenantId)
                        ->lockForUpdate()
                        ->first();
                    if ($state instanceof TenantOnboardingStateModel) {
                        $state->forceFill([
                            'status' => TenantOnboardingStatus::COMPLETED,
                            'operation_id' => null,
                            'operation_started_at' => null,
                            'operation_lease_expires_at' => null,
                            'completed_at' => $this->clock->now(),
                            'failed_step' => null,
                            'last_error_code' => null,
                            'last_error_message' => null,
                            'row_version' => (int) $state->getAttribute('row_version') + 1,
                            'updated_by' => $this->currentUser->currentUserId(),
                        ])->save();
                    }
                });
            }

            $actor = $this->actorSnapshots->current();
            $this->lifecycleEvents->newQuery()->create([
                'tenant_id' => $tenantId,
                'previous_status' => $currentStatus,
                'new_status' => $targetStatus,
                'reason' => $normalizedReason,
                'actor_id' => $actor['id'],
                'actor_type' => $actor['type'],
                'actor_name' => $actor['name'],
                'actor_email' => $actor['email'],
                'occurred_at' => $now,
            ]);

            $this->audit->recordPlatform(new AuditEventData(
                eventName: 'tenant.status_changed',
                eventCategory: AuditEventCategory::SECURITY,
                sourceModule: 'tenant',
                subjectType: 'tenant',
                subjectId: (string) $tenantId,
                subjectReference: (string) $updated->get('code'),
                changes: [
                    'old' => ['status' => $currentStatus],
                    'new' => [
                        'status' => $targetStatus,
                        'reason' => $normalizedReason,
                    ],
                ],
                tags: ['tenant', 'lifecycle'],
            ), $tenantId);
            $this->outbox->enqueueStatusChanged(
                tenantId: $tenantId,
                previousStatus: $currentStatus,
                newStatus: $targetStatus,
                reason: $normalizedReason,
            );

            return [
                'status' => self::OUTCOME_SUCCESS,
                'tenant' => $updated,
            ];
        });

        return match ($outcome['status']) {
            self::OUTCOME_SUCCESS => ($outcome['tenant'] ?? null) instanceof DataRecord
                ? Result::success($outcome['tenant'])
                : throw new RuntimeException('Lifecycle transition completed without an updated tenant record.'),
            self::OUTCOME_NOT_FOUND => $this->notFound(),
            self::OUTCOME_VERSION_CONFLICT => $this->versionConflict(),
            self::OUTCOME_INVALID_TRANSITION => $this->invalidTransition(
                (string) ($outcome['current_status'] ?? 'unknown'),
                $targetStatus,
            ),
            self::OUTCOME_READINESS_BLOCKED => $this->readinessFailure($outcome['readiness'] ?? []),
            default => throw new RuntimeException('Unknown tenant lifecycle transition outcome.'),
        };
    }

    private function notFound(): Result
    {
        return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Tenant changed since it was loaded. Refresh and try again.',
        ));
    }

    private function invalidTransition(string $currentStatus, string $targetStatus): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::INVALID_TRANSITION,
            "Tenant cannot transition from {$currentStatus} to {$targetStatus}.",
        ));
    }

    /** @param array<string, mixed> $readiness */
    private function readinessFailure(array $readiness): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::INVALID_VALUE,
            'Tenant activation is blocked until onboarding readiness checks pass.',
            ['readiness' => $readiness],
        ));
    }
}
