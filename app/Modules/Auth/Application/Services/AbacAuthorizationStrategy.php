<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthorizationStrategyInterface;

/**
 * Attribute-Based Access Control (ABAC) authorization strategy.
 * Delegates to Laravel's Gate / Policy system for fine-grained, attribute-aware checks.
 */
class AbacAuthorizationStrategy implements AuthorizationStrategyInterface
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function getName(): string
    {
        return 'abac';
    }

    public function authorize(int $userId, string $ability, mixed $subject = null): bool
    {
        $user = $this->userRepository->findAuthenticatable($userId);

        if (! $user) {
            return false;
        }

        try {
            return $subject !== null
                ? Gate::forUser($user)->allows($ability, $subject)
                : Gate::forUser($user)->allows($ability);
        } catch (\Throwable) {
            return false;
        }
    }
}
