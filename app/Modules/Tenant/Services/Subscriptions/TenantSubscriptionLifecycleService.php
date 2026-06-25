<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantSubscriptionEventType;
use Modules\Tenant\Constants\TenantSubscriptionOperation;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use Modules\Tenant\Services\Platform\TenantSchemaCompatibilityService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantSubscriptionLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantPlanRevisionRepositoryInterface $planRevisions,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantSubscriptionReadinessService $readiness,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantActorSnapshotFactory $actorSnapshots,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
        private readonly ClockInterface $clock,
        private readonly TenantSchemaCompatibilityService $schemaCompatibility,
        private readonly LoggerInterface $logger,
    ) {}

    /** @param array<string, mixed> $payload */
    public function assign(int $tenantId, array $payload): Result
    {
        return $this->createRevision(TenantSubscriptionOperation::ASSIGN, $tenantId, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function renew(int $tenantId, array $payload): Result
    {
        return $this->createRevision(TenantSubscriptionOperation::RENEW, $tenantId, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function extend(int $tenantId, array $payload): Result
    {
        return $this->createRevision(TenantSubscriptionOperation::EXTEND, $tenantId, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function correct(int $tenantId, array $payload): Result
    {
        return $this->createRevision(TenantSubscriptionOperation::CORRECT, $tenantId, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function cancel(int $tenantId, array $payload): Result
    {
        if (($schemaFailure = $this->schemaFailure()) !== null) {
            return $schemaFailure;
        }

        try {
            $expectedTenantVersion = $this->positiveInt($payload['expected_tenant_version'] ?? null);
            $expectedPointerVersion = $this->positiveInt($payload['expected_subscription_version'] ?? null);
            $reason = $this->requiredReason($payload['reason'] ?? null);
            if ($expectedTenantVersion === null || $expectedPointerVersion === null) {
                return $this->versionConflict();
            }

            return $this->executionContext->runForTenant($tenantId, function () use (
                $tenantId,
                $expectedTenantVersion,
                $expectedPointerVersion,
                $reason,
            ): Result {
                $outcome = $this->transactions->runInTransaction(function () use (
                    $tenantId,
                    $expectedTenantVersion,
                    $expectedPointerVersion,
                    $reason,
                ): ?DataRecord {
                    $tenant = $this->tenants->lockById($tenantId);
                    if ($tenant === null || (int) $tenant->require('row_version') !== $expectedTenantVersion) {
                        return null;
                    }

                    $current = $this->subscriptions->findCurrentByTenant($tenantId, true);
                    if (
                        $current === null
                        || (int) $current->get('row_version', 0) !== $expectedPointerVersion
                        || $current->get('current_state') !== TenantCurrentSubscriptionState::ASSIGNED
                    ) {
                        return null;
                    }

                    $updated = $this->subscriptions->transitionCurrentState(
                        $tenantId,
                        $expectedPointerVersion,
                        TenantCurrentSubscriptionState::CANCELLED,
                        $reason,
                        $this->currentUser->currentUserId(),
                    );
                    if ($updated === null) {
                        return null;
                    }

                    if ($this->tenants->updateWithVersion($tenantId, $expectedTenantVersion, [
                        'updated_by' => $this->currentUser->currentUserId(),
                    ]) === null) {
                        throw new RuntimeException('Locked tenant could not be versioned during subscription cancellation.');
                    }

                    $this->subscriptions->recordEvent(
                        $tenantId,
                        (int) $current->id(),
                        null,
                        TenantSubscriptionEventType::CANCELLED,
                        $reason,
                        $this->actorSnapshots->current(),
                        $this->clock->now(),
                    );
                    $this->recordAudit('cancelled', $tenant, $current, $updated, $reason);

                    return $updated;
                });

                return $outcome === null
                    ? $this->versionConflict()
                    : Result::success($outcome);
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'tenant.subscription.cancel', $tenantId);
        }
    }

    /** @param array<string, mixed> $payload */
    private function createRevision(string $operation, int $tenantId, array $payload): Result
    {
        if (($schemaFailure = $this->schemaFailure()) !== null) {
            return $schemaFailure;
        }

        try {
            $expectedTenantVersion = $this->positiveInt($payload['expected_tenant_version'] ?? null);
            $expectedPointerVersion = $this->positiveInt($payload['expected_subscription_version'] ?? null);
            if ($expectedTenantVersion === null) {
                return $this->versionConflict();
            }

            return $this->executionContext->runForTenant($tenantId, function () use (
                $operation,
                $tenantId,
                $payload,
                $expectedTenantVersion,
                $expectedPointerVersion,
            ): Result {
                $outcome = $this->transactions->runInTransaction(function () use (
                    $operation,
                    $tenantId,
                    $payload,
                    $expectedTenantVersion,
                    $expectedPointerVersion,
                ): array {
                    $tenant = $this->tenants->lockById($tenantId);
                    if ($tenant === null || (int) $tenant->require('row_version') !== $expectedTenantVersion) {
                        return ['conflict' => true];
                    }

                    $current = $this->subscriptions->findCurrentByTenant($tenantId, true);
                    if (! $this->operationAllowed($operation, $current, $expectedPointerVersion)) {
                        return ['conflict' => true];
                    }

                    $resolved = $this->resolveRevisionInput($operation, $payload, $current);
                    $planRevision = $this->planRevisions->findById($resolved['plan_revision_id'], true);
                    if ($planRevision === null) {
                        throw new InvalidArgumentException('The selected tenant plan revision does not exist.');
                    }
                    $this->assertPlanRevisionAvailable($planRevision, $resolved['period']['starts_at']);

                    $readiness = $this->readiness->inspect($tenantId, $resolved['plan_revision_id'], true);
                    if (! $readiness['ready']) {
                        return ['readiness' => $readiness];
                    }

                    $reason = $this->normalizeReason($payload['reason'] ?? $payload['change_reason'] ?? null);
                    if ($operation === TenantSubscriptionOperation::CORRECT && $reason === null) {
                        throw new InvalidArgumentException('A correction reason is required.');
                    }

                    $previousId = $current === null ? null : (int) $current->id();
                    $actor = $this->actorSnapshots->current();
                    $subscription = $this->subscriptions->createRevision($tenantId, [
                        'operation' => $operation,
                        'tenant_plan_revision_id' => $resolved['plan_revision_id'],
                        'supersedes_subscription_id' => $previousId,
                        'contract_status' => $resolved['contract_status'],
                        'starts_at' => $resolved['period']['starts_at'],
                        'trial_ends_at' => $resolved['period']['trial_ends_at'],
                        'ends_at' => $resolved['period']['ends_at'],
                        'change_reason' => $reason,
                        'plan_name' => (string) ($planRevision->get('plan')['name'] ?? ''),
                        'plan_slug' => (string) ($planRevision->get('plan')['slug'] ?? ''),
                        'plan_features_schema_version' => (int) $planRevision->get('features_schema_version', TenantPlanSchema::SCHEMA_VERSION),
                        'plan_features' => is_array($planRevision->get('features')) ? $planRevision->get('features') : [],
                        'plan_limits_schema_version' => (int) $planRevision->get('limits_schema_version', TenantPlanSchema::SCHEMA_VERSION),
                        'plan_limits' => is_array($planRevision->get('limits')) ? $planRevision->get('limits') : [],
                        'price' => $planRevision->get('price', 0),
                        'currency_code' => (string) ($planRevision->get('currency')['code'] ?? ''),
                        'currency_symbol' => $planRevision->get('currency')['symbol'] ?? null,
                        'billing_interval' => (string) $planRevision->get('billing_interval'),
                        'created_by_type' => $actor['type'],
                        'created_by_name' => $actor['name'],
                        'created_by_email' => $actor['email'],
                    ], $this->currentUser->currentUserId());

                    $assigned = $this->subscriptions->assignCurrent(
                        $tenantId,
                        (int) $subscription->id(),
                        $current === null ? null : $expectedPointerVersion,
                        $this->currentUser->currentUserId(),
                        $reason,
                    );
                    if ($assigned === null) {
                        throw new RuntimeException('Subscription pointer changed during assignment.');
                    }

                    if ($this->tenants->updateWithVersion($tenantId, $expectedTenantVersion, [
                        'updated_by' => $this->currentUser->currentUserId(),
                    ]) === null) {
                        throw new RuntimeException('Locked tenant could not be versioned during subscription assignment.');
                    }

                    $eventType = $this->eventType($operation);
                    $this->subscriptions->recordEvent(
                        $tenantId,
                        (int) $subscription->id(),
                        $previousId,
                        $eventType,
                        $reason,
                        $actor,
                        $this->clock->now(),
                    );
                    $this->recordAudit($eventType, $tenant, $current, $assigned, $reason);

                    return ['subscription' => $assigned];
                });

                if (($outcome['conflict'] ?? false) === true) {
                    return $this->versionConflict();
                }
                if (is_array($outcome['readiness'] ?? null)) {
                    return Result::failure(new Error(
                        TenantErrorCode::CONFLICT,
                        'The selected subscription revision cannot be assigned safely.',
                        ['readiness' => $outcome['readiness']],
                    ));
                }

                $subscription = $outcome['subscription'] ?? null;

                return $subscription instanceof DataRecord
                    ? Result::success($subscription)
                    : throw new RuntimeException('Subscription lifecycle operation completed without a current subscription.');
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'tenant.subscription.'.$operation, $tenantId);
        }
    }

    private function operationAllowed(string $operation, ?DataRecord $current, ?int $expectedPointerVersion): bool
    {
        if ($operation === TenantSubscriptionOperation::ASSIGN) {
            return $current === null
                ? $expectedPointerVersion === null
                : $expectedPointerVersion === (int) $current->get('row_version', 0)
                    && in_array($current->get('current_state'), [
                        TenantCurrentSubscriptionState::CANCELLED,
                        TenantCurrentSubscriptionState::EXPIRED,
                    ], true);
        }

        return $current !== null
            && $expectedPointerVersion !== null
            && $expectedPointerVersion === (int) $current->get('row_version', 0)
            && $current->get('current_state') === TenantCurrentSubscriptionState::ASSIGNED;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{plan_revision_id:int,contract_status:string,period:array{starts_at:string,trial_ends_at:?string,ends_at:?string}}
     */
    private function resolveRevisionInput(string $operation, array $payload, ?DataRecord $current): array
    {
        if ($operation === TenantSubscriptionOperation::EXTEND) {
            if ($current === null) {
                throw new InvalidArgumentException('A current subscription is required before it can be extended.');
            }
            $newEnd = $this->dateTime($payload['ends_at'] ?? null);
            $currentEnd = $this->dateTime($current->get('ends_at'));
            if ($newEnd === null || ($currentEnd !== null && $newEnd <= $currentEnd)) {
                throw new InvalidArgumentException('The extended end date must be later than the current end date.');
            }

            return [
                'plan_revision_id' => (int) $current->require('tenant_plan_revision_id'),
                'contract_status' => (string) $current->require('contract_status'),
                'period' => [
                    'starts_at' => $this->dateTime($current->require('starts_at'))?->format('Y-m-d H:i:s')
                        ?? throw new InvalidArgumentException('Current subscription start date is invalid.'),
                    'trial_ends_at' => $this->dateTime($current->get('trial_ends_at'))?->format('Y-m-d H:i:s'),
                    'ends_at' => $newEnd->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $planRevisionId = $this->positiveInt($payload['tenant_plan_revision_id'] ?? null);
        if ($planRevisionId === null) {
            throw new InvalidArgumentException('Select a tenant plan revision.');
        }
        $contractStatus = strtolower(trim((string) ($payload['contract_status'] ?? '')));
        if (! in_array($contractStatus, TenantSubscriptionStatus::assignable(), true)) {
            throw new InvalidArgumentException('Subscription contract status must be trial or active.');
        }

        $defaultStart = null;
        if ($operation === TenantSubscriptionOperation::RENEW) {
            if ($current === null) {
                throw new InvalidArgumentException('A current subscription is required before it can be renewed.');
            }
            $defaultStart = $this->dateTime($current->get('ends_at'))
                ?? $this->dateTime($current->get('trial_ends_at'));
            if ($defaultStart === null) {
                throw new InvalidArgumentException('An open-ended subscription cannot be renewed; correct or cancel it first.');
            }
        }

        return [
            'plan_revision_id' => $planRevisionId,
            'contract_status' => $contractStatus,
            'period' => $this->period($payload, $contractStatus, $defaultStart, $operation),
        ];
    }

    /** @param array<string, mixed> $payload @return array{starts_at:string,trial_ends_at:?string,ends_at:?string} */
    private function period(
        array $payload,
        string $contractStatus,
        ?DateTimeImmutable $defaultStart,
        string $operation,
    ): array
    {
        $startsAt = $this->dateTime($payload['starts_at'] ?? null) ?? $defaultStart ?? $this->clock->now();
        if (
            $operation === TenantSubscriptionOperation::RENEW
            && $defaultStart !== null
            && $startsAt < $defaultStart
        ) {
            throw new InvalidArgumentException(
                'A renewal cannot begin before the current subscription period ends.',
            );
        }

        $trialEndsAt = $this->dateTime($payload['trial_ends_at'] ?? null);
        $endsAt = $this->dateTime($payload['ends_at'] ?? null);

        if ($contractStatus === TenantSubscriptionStatus::TRIAL && $trialEndsAt === null) {
            throw new InvalidArgumentException('A trial end date is required for a trial subscription.');
        }
        if ($contractStatus === TenantSubscriptionStatus::ACTIVE) {
            $trialEndsAt = null;
        }
        if ($trialEndsAt !== null && $trialEndsAt <= $startsAt) {
            throw new InvalidArgumentException('Trial end date must be later than the subscription start date.');
        }
        if ($endsAt !== null && $endsAt <= $startsAt) {
            throw new InvalidArgumentException('Subscription end date must be later than the subscription start date.');
        }
        if ($trialEndsAt !== null && $endsAt !== null && $endsAt < $trialEndsAt) {
            throw new InvalidArgumentException('Subscription end date cannot be earlier than the trial end date.');
        }

        return [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'trial_ends_at' => $trialEndsAt?->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function assertPlanRevisionAvailable(DataRecord $revision, string $startsAt): void
    {
        $plan = is_array($revision->get('plan')) ? $revision->get('plan') : [];
        $currency = is_array($revision->get('currency')) ? $revision->get('currency') : [];
        if (! (bool) ($plan['is_active'] ?? false)) {
            throw new InvalidArgumentException('The selected tenant plan is inactive.');
        }
        if (! (bool) ($currency['is_active'] ?? false)) {
            throw new InvalidArgumentException('The selected plan currency is inactive.');
        }

        $effectiveAt = $this->dateTime($revision->get('effective_at'));
        if ($effectiveAt !== null && $effectiveAt > new DateTimeImmutable($startsAt)) {
            throw new InvalidArgumentException('The selected plan revision is not effective at the subscription start date.');
        }
    }

    private function eventType(string $operation): string
    {
        return match ($operation) {
            TenantSubscriptionOperation::ASSIGN => TenantSubscriptionEventType::ASSIGNED,
            TenantSubscriptionOperation::RENEW => TenantSubscriptionEventType::RENEWED,
            TenantSubscriptionOperation::EXTEND => TenantSubscriptionEventType::EXTENDED,
            TenantSubscriptionOperation::CORRECT => TenantSubscriptionEventType::CORRECTED,
            default => throw new InvalidArgumentException('Unsupported subscription lifecycle operation.'),
        };
    }

    private function recordAudit(
        string $eventType,
        DataRecord $tenant,
        ?DataRecord $previous,
        DataRecord $current,
        ?string $reason,
    ): void {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: "tenant.subscription.{$eventType}",
            eventCategory: AuditEventCategory::ADMINISTRATION,
            sourceModule: 'tenant',
            subjectType: 'tenant_subscription',
            subjectId: (string) $current->id(),
            subjectReference: (string) $tenant->get('code'),
            changes: [
                'old' => $previous === null ? null : $this->auditSnapshot($previous),
                'new' => $this->auditSnapshot($current),
            ],
            metadata: ['tenant_id' => (int) $tenant->id(), 'reason' => $reason],
            tags: ['tenant', 'subscription', 'platform'],
        ), (int) $tenant->id());
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(DataRecord $record): array
    {
        return array_intersect_key($record->toArray(), array_flip([
            'id',
            'revision_number',
            'contract_status',
            'starts_at',
            'trial_ends_at',
            'ends_at',
            'current_state',
        ]));
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Tenant or subscription changed while the operation was being completed. Refresh and try again.',
        ));
    }

    private function requiredReason(mixed $value): string
    {
        $reason = $this->normalizeReason($value);
        if ($reason === null) {
            throw new InvalidArgumentException('A reason is required.');
        }

        return $reason;
    }

    private function normalizeReason(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : mb_substr($value, 0, 500);
    }

    private function schemaFailure(): ?Result
    {
        $schema = $this->schemaCompatibility->inspect();
        if ($schema['compatible']) {
            return null;
        }

        return Result::failure(new Error(
            TenantErrorCode::SCHEMA_INCOMPATIBLE,
            'The deployed database schema is not compatible with tenant subscription management.',
            $schema,
        ));
    }

    private function failure(Throwable $exception, string $operation, int $tenantId): Result
    {
        if ($exception instanceof InvalidArgumentException) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => $operation, 'tenant_id' => $tenantId],
            ));
        }

        $correlationId = (string) Str::uuid();
        $this->logger->error('Tenant subscription mutation failed.', [
            'tenant_id' => $tenantId,
            'operation' => $operation,
            'correlation_id' => $correlationId,
            'exception' => $exception,
        ]);

        return Result::failure(new Error(
            TenantErrorCode::SUBSCRIPTION_DATA_UNAVAILABLE,
            'Tenant subscription data could not be changed.',
            ['operation' => $operation, 'correlation_id' => $correlationId],
        ));
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : new DateTimeImmutable($value);
    }
}
