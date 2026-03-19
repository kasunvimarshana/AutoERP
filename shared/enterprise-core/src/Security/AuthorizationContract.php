<?php

namespace Enterprise\Core\Security;

/**
 * AuthorizationContract - Interface for cross-service permission checks.
 * All microservices must implement this to ensure consistent RBAC/ABAC.
 */
interface AuthorizationContract
{
    /**
     * Determine if the current authenticated user has a specific permission.
     * @param string $permission Slug (e.g., 'inventory.adjust')
     * @param array $context Additional attributes for ABAC (e.g., ['amount' => 500])
     */
    public function can(string $permission, array $context = []): bool;

    /**
     * Get all active permissions for the current context.
     */
    public function getPermissions(): array;
}
