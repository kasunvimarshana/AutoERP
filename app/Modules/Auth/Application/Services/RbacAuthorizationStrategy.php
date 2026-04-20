<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthorizationStrategyInterface;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;

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
        return $this->userRepository->hasRole($userId, $ability)
            || $this->userRepository->hasPermission($userId, $ability);
    }
}
