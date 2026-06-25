<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Events\TenantStatusChanged;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class TenantLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantReadinessService $readiness,
        private readonly AuditRecorderInterface $audit,
        private readonly Dispatcher $events,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function transition(int|string $id, int $expectedVersion, string $targetStatus, ?string $reason = null): Result
    {
        return $this->transactions->runInTransaction(function () use (
            $id,
            $expectedVersion,
            $targetStatus,
            $reason,
        ): Result {
            $tenant = $this->tenants->findById($id);
            if ($tenant === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $current = (string) $tenant->require('status');
            if (! $this->transitionAllowed($current, $targetStatus)) {
                return Result::failure(new Error(
                    TenantErrorCode::INVALID_TRANSITION,
                    "Tenant cannot transition from {$current} to {$targetStatus}.",
                ));
            }

            if ($targetStatus === TenantStatus::ACTIVE) {
                $readiness = $this->readiness->evaluate($tenant);
                if (! $readiness->readyForActivation()) {
                    return Result::failure(new Error(
                        TenantErrorCode::INVALID_VALUE,
                        'Tenant is not ready for activation. '.implode(' ', $readiness->blockingMessages()),
                        ['failed_checks' => array_values(array_map(
                            static fn (array $check): string => $check['key'],
                            array_filter(
                                $readiness->checks,
                                static fn (array $check): bool => ! $check['ready'],
                            ),
                        ))],
                    ));
                }
            }

            $attributes = [
                'status' => $targetStatus,
                'status_reason' => $reason === null || trim($reason) === '' ? null : trim($reason),
                'activated_at' => $targetStatus === TenantStatus::ACTIVE
                    ? ($tenant->get('activated_at') ?? now())
                    : $tenant->get('activated_at'),
                'suspended_at' => $targetStatus === TenantStatus::SUSPENDED ? now() : null,
                'archived_at' => $targetStatus === TenantStatus::ARCHIVED ? now() : null,
                'updated_by' => $this->currentUser->currentUserId(),
            ];
            $updated = $this->tenants->updateWithVersion($id, $expectedVersion, $attributes);
            if ($updated === null) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant changed since it was loaded. Refresh and try again.',
                ));
            }

            $this->audit->record(new AuditEventData(
                eventName: 'tenant.status_changed',
                eventCategory: 'security',
                sourceModule: 'tenant',
                subjectType: 'tenant',
                subjectId: (string) $updated->id(),
                subjectReference: (string) $updated->get('code'),
                changes: [
                    'old' => ['status' => $current],
                    'new' => [
                        'status' => $targetStatus,
                        'reason' => $attributes['status_reason'],
                    ],
                ],
                tags: ['tenant', 'lifecycle'],
            ));
            $this->events->dispatch(new TenantStatusChanged(
                (int) $updated->id(),
                $current,
                $targetStatus,
                $attributes['status_reason'],
            ));

            return Result::success($updated);
        });
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
