<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeImmutable;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Events\TenantEventOutboxService;

final class TenantLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TenantEventOutboxService $outbox,
        private readonly ClockInterface $clock,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function transition(int|string $id, int $expectedVersion, string $targetStatus, ?string $reason = null): Result
    {
        $tenant = $this->tenants->findById($id);
        if ($tenant === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
        }
        $current = (string) $tenant->require('status');
        if (! $this->transitionAllowed($current, $targetStatus)) {
            return Result::failure(new Error(TenantErrorCode::INVALID_TRANSITION, "Tenant cannot transition from {$current} to {$targetStatus}."));
        }
        if ($targetStatus === TenantStatus::ACTIVE) {
            if ($tenant->get('base_currency_id') === null) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'A base accounting currency is required before activation.'));
            }
            if ($this->domains->findPrimaryByTenant((int) $tenant->id()) === null) {
                return Result::failure(new Error(TenantErrorCode::DOMAIN_NOT_VERIFIED, 'A verified primary domain is required before activation.'));
            }
            $planId = $tenant->get('tenant_plan_id');
            $plan = is_numeric($planId) ? $this->plans->findById((int) $planId) : null;
            if ($plan === null || ! (bool) $plan->get('is_active', false)) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_VALUE,
                    'An active subscription plan is required before activation.',
                ));
            }

            $subscriptionEndsAt = $tenant->get('subscription_ends_at');
            $trialEndsAt = $tenant->get('trial_ends_at');
            if ($this->isPast($subscriptionEndsAt)) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'An expired subscription cannot be activated.'));
            }
            if ($subscriptionEndsAt === null && $this->isPast($trialEndsAt)) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'An expired trial cannot be activated without a subscription.'));
            }
        }
        $now = $this->clock->now();
        $attributes = [
            'status' => $targetStatus,
            'status_reason' => $reason === null || trim($reason) === '' ? null : trim($reason),
            'activated_at' => $targetStatus === TenantStatus::ACTIVE
                ? ($tenant->get('activated_at') ?? $now)
                : $tenant->get('activated_at'),
            'suspended_at' => $targetStatus === TenantStatus::SUSPENDED ? $now : null,
            'archived_at' => $targetStatus === TenantStatus::ARCHIVED ? $now : null,
            'updated_by' => $this->currentUser->currentUserId(),
        ];

        $updated = $this->transactions->runInTransaction(function () use (
            $id,
            $expectedVersion,
            $attributes,
            $current,
            $targetStatus,
        ) {
            $updated = $this->tenants->updateWithVersion($id, $expectedVersion, $attributes);
            if ($updated === null) {
                return null;
            }

            $tenantId = (int) $updated->id();
            $this->audit->recordPlatform(new AuditEventData(
                eventName: 'tenant.status_changed',
                eventCategory: 'security',
                sourceModule: 'tenant',
                subjectType: 'tenant',
                subjectId: (string) $tenantId,
                subjectReference: (string) $updated->get('code'),
                changes: [
                    'old' => ['status' => $current],
                    'new' => [
                        'status' => $targetStatus,
                        'reason' => $attributes['status_reason'],
                    ],
                ],
                tags: ['tenant', 'lifecycle'],
            ), $tenantId);
            $this->outbox->enqueueStatusChanged(
                tenantId: $tenantId,
                previousStatus: $current,
                newStatus: $targetStatus,
                reason: $attributes['status_reason'],
            );

            return $updated;
        });

        if ($updated === null) {
            return Result::failure(new Error(
                TenantErrorCode::VERSION_CONFLICT,
                'Tenant changed since it was loaded. Refresh and try again.',
            ));
        }

        return Result::success($updated);
    }

    private function isPast(mixed $value): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return false;
        }

        return new DateTimeImmutable((string) $value) < $this->clock->now();
    }

    private function transitionAllowed(string $from, string $to): bool
    {
        return in_array($to, match ($from) {
            TenantStatus::DRAFT => [TenantStatus::ACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::ACTIVE => [TenantStatus::SUSPENDED, TenantStatus::INACTIVE],
            TenantStatus::SUSPENDED => [TenantStatus::ACTIVE, TenantStatus::INACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::INACTIVE => [TenantStatus::ACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::ARCHIVED => [],
            default => [],
        }, true);
    }
}
