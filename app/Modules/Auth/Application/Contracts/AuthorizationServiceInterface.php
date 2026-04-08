<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

/**
 * Contract for the authorization service.
 * Delegates to one or more pluggable AuthorizationStrategyInterface implementations.
 */
interface AuthorizationServiceInterface
{
    /**
     * Check if the user has the given role.
     */
    public function hasRole(int $userId, string $role): bool;

    /**
     * Check if the user has the given permission.
     */
    public function hasPermission(int $userId, string $permission): bool;

    /**
     * Check authorization via the configured strategy (RBAC, ABAC, etc.).
     *
     * @param  mixed  $subject  Optional subject for ABAC checks
     */
    public function can(int $userId, string $ability, mixed $subject = null): bool;

    /**
     * Add a pluggable authorization strategy at runtime.
     */
    public function addStrategy(AuthorizationStrategyInterface $strategy): void;
}
