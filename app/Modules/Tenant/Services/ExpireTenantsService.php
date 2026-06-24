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
use Modules\Tenant\Constants\TenantStatus;
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

            foreach ($this->subscriptions->listExpiredCurrent($now, $batchSize) as $subscription) {
                $summary['checked']++;
                $tenantId = (int) $subscription->require('tenant_id');

                $updated = $this->executionContext->runForTenant(
                    $tenantId,
                    fn () => $this->transactions->runInTransaction(function () use ($subscription, $tenantId) {
                        $tenant = $this->tenants->lockById($tenantId);
                        if ($tenant === null || $tenant->get('status') !== TenantStatus::ACTIVE) {
                            return null;
                        }

                        if (! $this->subscriptions->expireWithVersion(
                            $subscription->id(),
                            (int) $subscription->require('row_version'),
                        )) {
                            return null;
                        }

                        $updated = $this->tenants->updateWithVersion(
                            $tenantId,
                            (int) $tenant->require('row_version'),
                            [
                                'status' => TenantStatus::INACTIVE,
                                'status_reason' => self::EXPIRY_REASON,
                                'suspended_at' => null,
                                'updated_by' => null,
                            ],
                        );
                        if ($updated === null) {
                            return null;
                        }

                        $this->audit->recordSystem(new SystemAuditEventData(
                            event: new AuditEventData(
                                eventName: 'tenant.subscription_expired',
                                eventCategory: AuditEventCategory::SECURITY,
                                sourceModule: 'tenant',
                                subjectType: 'tenant_subscription',
                                subjectId: (string) $subscription->id(),
                                subjectReference: (string) $updated->get('code'),
                                changes: [
                                    'old' => ['tenant_status' => TenantStatus::ACTIVE, 'subscription_status' => $subscription->get('status')],
                                    'new' => ['tenant_status' => TenantStatus::INACTIVE, 'subscription_status' => 'expired'],
                                ],
                                metadata: ['reason' => self::EXPIRY_REASON],
                                tags: ['tenant', 'subscription', 'lifecycle'],
                            ),
                            actorType: AuditActorType::JOB,
                            actorId: 'tenant-expiry',
                            actorName: 'Tenant subscription expiry job',
                            tenantId: $tenantId,
                        ));
                        $this->outbox->enqueueStatusChanged(
                            tenantId: $tenantId,
                            previousStatus: TenantStatus::ACTIVE,
                            newStatus: TenantStatus::INACTIVE,
                            reason: self::EXPIRY_REASON,
                        );

                        return $updated;
                    }),
                );

                if ($updated === null) {
                    $summary['conflicts']++;
                } else {
                    $summary['expired']++;
                }
            }

            return $summary;
        });
    }
}
