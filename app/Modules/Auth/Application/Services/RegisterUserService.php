<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\RegisterUserServiceInterface;

class RegisterUserService implements RegisterUserServiceInterface
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function register(array $data): int
    {
        return $this->userRepository->createUser($data);
    }
}
