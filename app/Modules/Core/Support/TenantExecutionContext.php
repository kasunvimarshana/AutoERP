<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use InvalidArgumentException;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class TenantExecutionContext implements TenantExecutionContextInterface
{
    /** @var list<int> */
    private array $tenantStack = [];

    private int $controlPlaneDepth = 0;

    public function tenantId(): ?int
    {
        $tenantId = end($this->tenantStack);

        return is_int($tenantId) && $tenantId > 0 ? $tenantId : null;
    }

    public function isActive(): bool
    {
        return $this->tenantId() !== null;
    }

    public function isControlPlane(): bool
    {
        return $this->controlPlaneDepth > 0;
    }

    public function runForTenant(int $tenantId, callable $callback): mixed
    {
        if ($tenantId < 1) {
            throw new InvalidArgumentException('Tenant execution context requires a positive tenant identifier.');
        }

        $currentTenantId = $this->tenantId();
        if ($currentTenantId !== null && $currentTenantId !== $tenantId) {
            throw new InvalidArgumentException('Nested tenant execution cannot switch tenant ownership.');
        }

        $this->tenantStack[] = $tenantId;

        try {
            return $callback();
        } finally {
            array_pop($this->tenantStack);
        }
    }

    public function runAsControlPlane(callable $callback): mixed
    {
        if ($this->isActive()) {
            throw new LogicException('Tenant-scoped execution cannot broaden into the platform control plane.');
        }

        $this->controlPlaneDepth++;

        try {
            return $callback();
        } finally {
            $this->controlPlaneDepth--;
        }
    }
}
