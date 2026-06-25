<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Constants\TenantSubscriptionEventType;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Events\TenantEventOutboxService;

final class ExpireTenantsService
{
    private const DEFAULT_LIMIT = 100;
    private const MAX_LIMIT = 500;
    private const EXPIRY_REASON = 'Subscription or trial expired.';

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly AuditRecorderInterface $audit,
        private readonly TenantEventOutboxService $outbox,
        private readonly ClockInterface $clock,
        private readonly TransactionManagerInterface $transactions,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return array{checked:int,expired:int,conflicts:int} */
    public function execute(?int $limit = null): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($limit): array {
            $now = $this->clock->now();
            $batchSize = max(1, min($limit ?? self::DEFAULT_LIMIT, self::MAX_LIMIT));
            $summary = ['checked' => 0, 'expired' => 0, 'conflicts' => 0];

            foreach ($this->subscriptions->listExpiredCurrent($now, $batchSize) as $candidate) {
                $summary['checked']++;
                $tenantId = (int) $candidate->require('tenant_id');

                $expired = $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): bool => $this->transactions->runInTransaction(function () use ($candidate, $tenantId, $now): bool {
                        $tenant = $this->tenants->lockById($tenantId);
                        $current = $this->subscriptions->findCurrentByTenant($tenantId, true);
                        if (
                            $tenant === null
                            || $current === null
                            || (int) $current->id() !== (int) $candidate->id()
                            || $current->get('current_state') !== TenantCurrentSubscriptionState::ASSIGNED
                            || (int) $current->get('row_version', 0) !== (int) $candidate->get('row_version', 0)
                        ) {
                            return false;
                        }

                        $updatedSubscription = $this->subscriptions->transitionCurrentState(
                            $tenantId,
                            (int) $current->require('row_version'),
                            TenantCurrentSubscriptionState::EXPIRED,
                            self::EXPIRY_REASON,
                            null,
                        );
                        if ($updatedSubscription === null) {
                            return false;
                        }

                        $previousTenantStatus = (string) $tenant->require('status');
                        $updatedTenant = $tenant;
                        if ($previousTenantStatus === TenantStatus::ACTIVE) {
                            $updatedTenant = $this->tenants->updateWithVersion(
                                $tenantId,
                                (int) $tenant->require('row_version'),
                                [
                                    'status' => TenantStatus::INACTIVE,
                                    'status_reason' => self::EXPIRY_REASON,
                                    'updated_by' => null,
                                ],
                            );
                            if ($updatedTenant === null) {
                                return false;
                            }
                        }

                        $this->subscriptions->recordEvent(
                            $tenantId,
                            (int) $current->id(),
                            null,
                            TenantSubscriptionEventType::EXPIRED,
                            self::EXPIRY_REASON,
                            null,
                            $now,
                        );
                        $this->audit->recordSystem(new SystemAuditEventData(
                            event: new AuditEventData(
                                eventName: 'tenant.subscription_expired',
                                eventCategory: AuditEventCategory::SECURITY,
                                sourceModule: 'tenant',
                                subjectType: 'tenant_subscription',
                                subjectId: (string) $current->id(),
                                subjectReference: (string) $updatedTenant->get('code'),
                                changes: [
                                    'old' => [
                                        'tenant_status' => $previousTenantStatus,
                                        'subscription_state' => TenantCurrentSubscriptionState::ASSIGNED,
                                    ],
                                    'new' => [
                                        'tenant_status' => (string) $updatedTenant->get('status'),
                                        'subscription_state' => TenantCurrentSubscriptionState::EXPIRED,
                                    ],
                                ],
                                metadata: ['reason' => self::EXPIRY_REASON],
                                tags: ['tenant', 'subscription', 'lifecycle'],
                            ),
                            actorType: AuditActorType::JOB,
                            actorId: 'tenant-expiry',
                            actorName: 'Tenant subscription expiry job',
                            tenantId: $tenantId,
                        ));

                        if ($previousTenantStatus === TenantStatus::ACTIVE) {
                            $this->outbox->enqueueStatusChanged(
                                tenantId: $tenantId,
                                previousStatus: TenantStatus::ACTIVE,
                                newStatus: TenantStatus::INACTIVE,
                                reason: self::EXPIRY_REASON,
                            );
                        }

                        return true;
                    }),
                );

                $summary[$expired ? 'expired' : 'conflicts']++;
            }

            return $summary;
        });
    }
}
