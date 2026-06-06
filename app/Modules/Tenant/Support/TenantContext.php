<?php

declare(strict_types=1);

namespace Modules\Tenant\Support;

use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;

final class TenantContext
{
    public function __construct(private readonly CurrentTenantContextAccessorInterface $currentTenant) {}

    public function current(): ?CurrentTenantContext
    {
        return $this->currentTenant->current();
    }

    public function tenantId(): ?int
    {
        return $this->currentTenant->currentTenantId();
    }

    public function requireTenantId(): int
    {
        $tenantId = $this->tenantId();
        if ($tenantId === null || $tenantId < 1) {
            throw new CurrentTenantContextResolutionException('Tenant context is required for this operation.');
        }

        return $tenantId;
    }

    public function resolveTenantId(?int $candidateTenantId): int
    {
        $contextTenantId = $this->requireTenantId();

        if ($candidateTenantId === null || $candidateTenantId < 1) {
            return $contextTenantId;
        }

        if ($candidateTenantId !== $contextTenantId) {
            throw new CurrentTenantContextResolutionException('Tenant scope mismatch for the active request.');
        }

        return $contextTenantId;
    }
}
