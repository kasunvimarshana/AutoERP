<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Directory;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Data\TenantConfigurationTarget;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Services\Contracts\TenantConfigurationTargetReaderInterface;

final class TenantConfigurationTargetReader implements TenantConfigurationTargetReaderInterface
{
    public function __construct(
        private readonly TenantModel $tenants,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function find(int $tenantId): ?TenantConfigurationTarget
    {
        if ($tenantId < 1) {
            return null;
        }

        return $this->executionContext->runAsControlPlane(function () use ($tenantId): ?TenantConfigurationTarget {
            /** @var TenantModel|null $tenant */
            $tenant = $this->tenants->newQuery()
                ->select(['id', 'status'])
                ->find($tenantId);

            if ($tenant === null) {
                return null;
            }

            return new TenantConfigurationTarget(
                id: (int) $tenant->getAttribute('id'),
                status: (string) $tenant->getAttribute('status'),
            );
        });
    }

    public function count(): int
    {
        return $this->executionContext->runAsControlPlane(
            fn (): int => $this->tenants->newQuery()->count(),
        );
    }
}
