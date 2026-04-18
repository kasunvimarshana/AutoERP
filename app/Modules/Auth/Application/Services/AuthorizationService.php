<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Application\Contracts\AuthorizationStrategyInterface;

/**
 * Composite authorization service.
 * Holds a primary strategy and additional pluggable strategies.
 * Falls back through strategies until one grants access.
 */
class AuthorizationService implements AuthorizationServiceInterface
{
    /** @var AuthorizationStrategyInterface[] */
    private array $strategies = [];

    public function __construct(
        private readonly AuthUserRepositoryInterface $userRepository,
        AuthorizationStrategyInterface ...$strategies,
    ) {
        $this->strategies = $strategies;
    }

    public function hasRole(int $userId, string $role): bool
    {
        return $this->userRepository->hasRole($userId, $role);
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        return $this->userRepository->hasPermission($userId, $permission);
    }

    public function can(int $userId, string $ability, mixed $subject = null): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->authorize($userId, $ability, $subject)) {
                return true;
            }
        }

        return false;
    }

    public function addStrategy(AuthorizationStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }
}
