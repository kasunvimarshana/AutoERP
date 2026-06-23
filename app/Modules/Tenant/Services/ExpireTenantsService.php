<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Events\TenantEventOutboxService;

final class ExpireTenantsService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly AuditRecorderInterface $audit,
        private readonly TenantEventOutboxService $outbox,
        private readonly ClockInterface $clock,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    /** @return array{checked:int,expired:int,conflicts:int} */
    public function execute(?int $limit = null): array
    {
        $now = $this->clock->now();
        $summary = ['checked' => 0, 'expired' => 0, 'conflicts' => 0];

        foreach ($this->tenants->listExpiredActive($now, $limit ?? 100) as $tenant) {
            $summary['checked']++;
            $updated = $this->transactions->runInTransaction(function () use ($tenant) {
                $updated = $this->tenants->updateWithVersion(
                    $tenant->id(),
                    (int) $tenant->get('row_version', 0),
                    [
                        'status' => TenantStatus::INACTIVE,
                        'status_reason' => 'Subscription or trial expired.',
                        'suspended_at' => null,
                        'updated_by' => null,
                    ],
                );
                if ($updated === null) {
                    return null;
                }

                $tenantId = (int) $updated->id();
                $this->audit->recordSystem(new SystemAuditEventData(
                    event: new AuditEventData(
                        eventName: 'tenant.subscription_expired',
                        eventCategory: 'security',
                        sourceModule: 'tenant',
                        subjectType: 'tenant',
                        subjectId: (string) $tenantId,
                        subjectReference: (string) $updated->get('code'),
                        changes: [
                            'old' => ['status' => TenantStatus::ACTIVE],
                            'new' => [
                                'status' => TenantStatus::INACTIVE,
                                'reason' => 'Subscription or trial expired.',
                            ],
                        ],
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
                    reason: 'Subscription or trial expired.',
                );

                return $updated;
            });

            if ($updated === null) {
                $summary['conflicts']++;
                continue;
            }

            $summary['expired']++;
        }

        return $summary;
    }
}
