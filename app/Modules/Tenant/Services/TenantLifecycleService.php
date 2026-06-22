<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Events\TenantStatusChanged;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class TenantLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly Dispatcher $events,
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
            $subscriptionEndsAt = $tenant->get('subscription_ends_at');
            $trialEndsAt = $tenant->get('trial_ends_at');
            if ($subscriptionEndsAt !== null && strtotime((string) $subscriptionEndsAt) < time()) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'An expired subscription cannot be activated.'));
            }
            if ($subscriptionEndsAt === null && $trialEndsAt !== null && strtotime((string) $trialEndsAt) < time()) {
                return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, 'An expired trial cannot be activated without a subscription.'));
            }
        }
        $attributes = [
            'status' => $targetStatus,
            'status_reason' => $reason === null || trim($reason) === '' ? null : trim($reason),
            'activated_at' => $targetStatus === TenantStatus::ACTIVE ? ($tenant->get('activated_at') ?? now()) : $tenant->get('activated_at'),
            'suspended_at' => $targetStatus === TenantStatus::SUSPENDED ? now() : null,
            'archived_at' => $targetStatus === TenantStatus::ARCHIVED ? now() : null,
            'updated_by' => $this->currentUser->currentUserId(),
        ];
        $updated = $this->tenants->updateWithVersion($id, $expectedVersion, $attributes);
        if ($updated === null) {
            return Result::failure(new Error(TenantErrorCode::VERSION_CONFLICT, 'Tenant changed since it was loaded. Refresh and try again.'));
        }
        $this->events->dispatch(new TenantStatusChanged((int) $updated->id(), $current, $targetStatus, $attributes['status_reason']));
        $this->audit->record(new AuditEventData(
            eventName: 'tenant.status_changed', eventCategory: 'security', sourceModule: 'tenant',
            subjectType: 'tenant', subjectId: (string) $updated->id(), subjectReference: (string) $updated->get('code'),
            changes: ['old' => ['status' => $current], 'new' => ['status' => $targetStatus, 'reason' => $attributes['status_reason']]],
            tags: ['tenant', 'lifecycle'],
        ));
        return Result::success($updated);
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
