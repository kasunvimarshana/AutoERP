<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use DateTimeImmutable;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use RuntimeException;
use Throwable;

final class AssignTenantSubscriptionService
{
    private const OUTCOME_SUCCESS = 'success';
    private const OUTCOME_VERSION_CONFLICT = 'version_conflict';
    private const OUTCOME_READINESS_BLOCKED = 'readiness_blocked';

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantPlanRevisionRepositoryInterface $revisions,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantSubscriptionReadinessService $readiness,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(int|string $tenantId, array $payload): Result
    {
        try {
            $tenant = $this->tenants->findById($tenantId);
            if ($tenant === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $expectedVersion = (int) ($payload['expected_tenant_version'] ?? 0);
            if ($expectedVersion < 1 || (int) $tenant->require('row_version') !== $expectedVersion) {
                return $this->versionConflict();
            }

            $revisionId = (int) ($payload['tenant_plan_revision_id'] ?? 0);
            $revision = $this->revisions->findById($revisionId);
            if ($revision === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan revision not found.'));
            }

            $status = strtolower(trim((string) ($payload['status'] ?? '')));
            if (! in_array($status, TenantSubscriptionStatus::assignable(), true)) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'Subscription status must be trial or active.'));
            }

            $period = $this->period($payload, $status);
            $effectiveAt = $this->dateTime($revision->get('effective_at'));
            $startsAt = new DateTimeImmutable($period['starts_at']);
            if ($effectiveAt !== null && $effectiveAt > $startsAt) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'The selected plan revision is not effective at the subscription start date.',
                    ['effective_at' => $effectiveAt->format(DATE_ATOM)],
                ));
            }

            $preflight = $this->readiness->inspect((int) $tenant->id(), $revisionId);
            if (! $preflight['ready']) {
                return $this->readinessFailure($preflight);
            }

            /**
             * @var array{
             *   status:string,
             *   subscription?:DataRecord,
             *   readiness?:array<string,mixed>
             * } $outcome
             */
            $outcome = $this->transactions->runInTransaction(function () use (
                $tenant,
                $expectedVersion,
                $revisionId,
                $status,
                $period,
                $payload,
            ): array {
                $tenantId = (int) $tenant->id();
                $locked = $this->tenants->lockById($tenantId);
                if ($locked === null || (int) $locked->require('row_version') !== $expectedVersion) {
                    return ['status' => self::OUTCOME_VERSION_CONFLICT];
                }

                // Usage, module-closeout, plan availability, and current subscription can
                // change after the preflight response. The locked tenant row serializes
                // subscription assignment while this final authoritative gate is evaluated.
                $readiness = $this->readiness->inspect($tenantId, $revisionId);
                if (! $readiness['ready']) {
                    return [
                        'status' => self::OUTCOME_READINESS_BLOCKED,
                        'readiness' => $readiness,
                    ];
                }

                $updatedTenant = $this->tenants->updateWithVersion($tenantId, $expectedVersion, [
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updatedTenant === null) {
                    throw new RuntimeException('Locked tenant could not be versioned during subscription assignment.');
                }

                $subscription = $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): DataRecord => $this->subscriptions->replaceCurrent(
                        $tenantId,
                        [
                            'tenant_plan_revision_id' => $revisionId,
                            'status' => $status,
                            'starts_at' => $period['starts_at'],
                            'trial_ends_at' => $period['trial_ends_at'],
                            'ends_at' => $period['ends_at'],
                            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
                        ],
                        $this->currentUser->currentUserId(),
                    ),
                );

                return [
                    'status' => self::OUTCOME_SUCCESS,
                    'subscription' => $subscription,
                ];
            });

            if ($outcome['status'] === self::OUTCOME_VERSION_CONFLICT) {
                return $this->versionConflict();
            }
            if ($outcome['status'] === self::OUTCOME_READINESS_BLOCKED) {
                return $this->readinessFailure($outcome['readiness'] ?? []);
            }

            $subscription = $outcome['subscription'] ?? null;
            if (! $subscription instanceof DataRecord) {
                throw new RuntimeException('Subscription assignment completed without a subscription record.');
            }

            $this->audit->recordPlatform(new AuditEventData(
                eventName: 'tenant.subscription.assigned',
                eventCategory: AuditEventCategory::ADMINISTRATION,
                sourceModule: 'tenant',
                subjectType: 'tenant_subscription',
                subjectId: (string) $subscription->id(),
                subjectReference: (string) $tenant->get('code'),
                changes: ['new' => $subscription->toArray()],
                metadata: ['tenant_id' => (int) $tenant->id()],
                tags: ['tenant', 'subscription', 'platform'],
            ), (int) $tenant->id());

            return Result::success($subscription);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.subscription.assign', 'tenant_id' => (string) $tenantId],
            ));
        }
    }

    /** @param array<string, mixed> $payload @return array{starts_at:string,trial_ends_at:?string,ends_at:?string} */
    private function period(array $payload, string $status): array
    {
        $now = new DateTimeImmutable('now');
        $startsAt = $this->dateTime($payload['starts_at'] ?? null) ?? $now;
        $trialEndsAt = $this->dateTime($payload['trial_ends_at'] ?? null);
        $endsAt = $this->dateTime($payload['ends_at'] ?? null);

        if ($startsAt > $now) {
            throw new \InvalidArgumentException('Scheduled subscriptions are not supported by this immediate assignment endpoint.');
        }
        if ($status === TenantSubscriptionStatus::TRIAL && $trialEndsAt === null) {
            throw new \InvalidArgumentException('A trial end date is required for a trial subscription.');
        }
        if ($trialEndsAt !== null && $trialEndsAt <= $startsAt) {
            throw new \InvalidArgumentException('Trial end date must be later than the subscription start date.');
        }
        if ($endsAt !== null && $endsAt <= $startsAt) {
            throw new \InvalidArgumentException('Subscription end date must be later than the subscription start date.');
        }
        if ($trialEndsAt !== null && $endsAt !== null && $endsAt < $trialEndsAt) {
            throw new \InvalidArgumentException('Subscription end date cannot be earlier than the trial end date.');
        }
        if ($status === TenantSubscriptionStatus::ACTIVE) {
            $trialEndsAt = null;
        }

        return [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'trial_ends_at' => $trialEndsAt?->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : new DateTimeImmutable($value);
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Tenant changed while assigning the subscription. Refresh and try again.',
        ));
    }

    /** @param array<string, mixed> $readiness */
    private function readinessFailure(array $readiness): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::CONFLICT,
            'The selected subscription cannot be assigned safely.',
            ['readiness' => $readiness],
        ));
    }
}
