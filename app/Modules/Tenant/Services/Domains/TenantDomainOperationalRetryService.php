<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Jobs\VerifyTenantDomainOperationalReadiness;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;

final class TenantDomainOperationalRetryService
{
    private const MAX_BATCH_SIZE = 100;

    public function __construct(
        private readonly TenantDomainModel $domains,
        private readonly TenantDomainRepositoryInterface $repository,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly ClockInterface $clock,
    ) {}

    public function queueFailed(?int $tenantId = null, int $limit = 50): int
    {
        $batchSize = max(1, min($limit, self::MAX_BATCH_SIZE));

        /** @var list<array{id:int,tenant_id:int,row_version:int}> $candidates */
        $candidates = $this->executionContext->runAsControlPlane(function () use ($tenantId, $batchSize): array {
            return $this->domains->newQuery()
                ->select(['id', 'tenant_id', 'row_version'])
                ->where('status', TenantDomainStatus::PENDING)
                ->where('ownership_status', TenantDomainOwnershipStatus::VERIFIED)
                ->where('operational_status', TenantDomainOperationalStatus::FAILED)
                ->when($tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $tenantId))
                ->orderBy('operational_retry_at')
                ->orderBy('id')
                ->limit($batchSize)
                ->get()
                ->map(static fn (TenantDomainModel $domain): array => [
                    'id' => (int) $domain->getKey(),
                    'tenant_id' => (int) $domain->getAttribute('tenant_id'),
                    'row_version' => (int) $domain->getAttribute('row_version'),
                ])
                ->all();
        });

        $queued = 0;
        foreach ($candidates as $candidate) {
            $updated = $this->executionContext->runForTenant(
                $candidate['tenant_id'],
                fn () => $this->repository->updateWithVersion(
                    $candidate['id'],
                    $candidate['tenant_id'],
                    $candidate['row_version'],
                    [
                        'routing_status' => TenantDomainCheckStatus::CHECKING,
                        'tls_status' => TenantDomainCheckStatus::CHECKING,
                        'reachability_status' => TenantDomainCheckStatus::CHECKING,
                        'operational_status' => TenantDomainOperationalStatus::CHECKING,
                        'operational_error_code' => null,
                        'operational_error_message' => null,
                        'operational_retry_at' => null,
                        'operational_claim_token' => null,
                        'operational_claimed_at' => null,
                        'operational_claim_lease_expires_at' => null,
                    ],
                ),
            );

            if ($updated === null) {
                continue;
            }

            VerifyTenantDomainOperationalReadiness::dispatch(
                $candidate['tenant_id'],
                $candidate['id'],
            );
            $queued++;
        }

        return $queued;
    }
}
