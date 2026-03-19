<?php

namespace Enterprise\Core\Tenancy;

/**
 * TenantContext - A global singleton that holds the current tenant's hierarchy.
 * Injected into database queries, caches, and queues.
 */
class TenantContext
{
    private ?string $tenantId = null;
    private ?string $organizationId = null;
    private ?string $branchId = null;
    private ?string $locationId = null;
    private ?string $departmentId = null;

    private array $config = [];

    public function setContext(string $tenantId, ?string $orgId = null, ?string $branchId = null, ?string $locationId = null, ?string $deptId = null)
    {
        $this->tenantId = $tenantId;
        $this->organizationId = $orgId;
        $this->branchId = $branchId;
        $this->locationId = $locationId;
        $this->departmentId = $deptId;
    }

    public function getTenantId(): ?string { return $this->tenantId; }
    public function getOrganizationId(): ?string { return $this->organizationId; }
    public function getBranchId(): ?string { return $this->branchId; }
    public function getLocationId(): ?string { return $this->locationId; }
    public function getDepartmentId(): ?string { return $this->departmentId; }

    public function setConfig(array $config) { $this->config = $config; }
    public function getConfig(string $key, $default = null) { return $this->config[$key] ?? $default; }

    public function isSet(): bool { return !is_null($this->tenantId); }
}
