<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthorizationStrategyInterface;

/**
 * Role-Based Access Control (RBAC) authorization strategy.
 * Checks whether a user has a given role or permission via the roles/permissions tables.
 */
class RbacAuthorizationStrategy implements AuthorizationStrategyInterface
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function getName(): string
    {
        return 'rbac';
    }

    public function authorize(int $userId, string $ability, mixed $subject = null): bool
    {
        $roles = $this->userRepository->getRolesWithPermissions($userId);

        foreach ($roles as $role) {
            // Direct role name match
            if (strtolower($role['name']) === strtolower($ability)) {
                return true;
            }

            // Permission name match on the role
            if (in_array($ability, $role['permissions'], strict: true)) {
                return true;
            }
        }

        return false;
    }
}
