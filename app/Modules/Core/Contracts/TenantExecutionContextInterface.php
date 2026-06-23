<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantExecutionContextInterface
{
    public function tenantId(): ?int;

    public function isActive(): bool;

    public function isControlPlane(): bool;

    /**
     * Execute work inside an explicit tenant boundary.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function runForTenant(int $tenantId, callable $callback): mixed;

    /**
     * Execute a deliberately cross-tenant control-plane operation.
     *
     * This boundary is intended only for trusted platform workflows such as
     * domain resolution and scheduled lifecycle workers. Tenant-facing code
     * must use runForTenant instead.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function runAsControlPlane(callable $callback): mixed;
}
