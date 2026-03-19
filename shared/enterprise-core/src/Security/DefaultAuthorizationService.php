<?php

namespace Enterprise\Core\Security;

use Enterprise\Core\Tenancy\TenantContext;

/**
 * DefaultAuthorizationService - Implements RBAC/ABAC logic for microservices.
 * Uses claims from the decoded JWT.
 */
class DefaultAuthorizationService implements AuthorizationContract
{
    protected array $userContext;
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext, array $userContext = [])
    {
        $this->tenantContext = $tenantContext;
        $this->userContext = $userContext;
    }

    /**
     * Set the current user context (usually from the JWT).
     */
    public function setUserContext(array $context)
    {
        $this->userContext = $context;
    }

    /**
     * RBAC & ABAC check.
     */
    public function can(string $permission, array $context = []): bool
    {
        if (empty($this->userContext)) {
            return false;
        }

        // 1. RBAC: Check if permission is in the JWT claims
        $permissions = $this->userContext['permissions'] ?? [];
        $hasPermission = in_array($permission, $permissions);

        if (!$hasPermission) {
            return false;
        }

        // 2. ABAC: Implement dynamic attribute-based checks
        // Example: Only managers in a specific branch can perform certain actions
        if (!empty($context)) {
            return $this->evaluateABAC($permission, $context);
        }

        return true;
    }

    /**
     * Evaluate Attribute-Based Access Control logic.
     */
    protected function evaluateABAC(string $permission, array $context): bool
    {
        // Example ABAC logic:
        // if ($permission === 'order.approve' && isset($context['amount'])) {
        //     return $this->userContext['role'] === 'manager' && $context['amount'] < 50000;
        // }
        
        // This logic can be further abstracted to use a Rule Engine from MetadataEngine
        return true; 
    }

    public function getPermissions(): array
    {
        return $this->userContext['permissions'] ?? [];
    }
}
