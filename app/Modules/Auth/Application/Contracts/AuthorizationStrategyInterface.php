<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

/**
 * Pluggable authorization strategy contract.
 * Implement this interface to add RBAC, ABAC, or any other authorization strategy.
 */
interface AuthorizationStrategyInterface
{
    /**
     * Determine whether the given user is authorized for the given ability.
     *
     * @param  string  $ability  e.g. 'view-users', 'admin', a role name, or policy method
     * @param  mixed  $subject  Optional subject for ABAC (model instance, array of attributes, etc.)
     */
    public function authorize(int $userId, string $ability, mixed $subject = null): bool;

    /**
     * Return the strategy name (e.g. 'rbac', 'abac').
     */
    public function getName(): string;
}
