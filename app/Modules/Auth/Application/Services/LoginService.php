<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\LoginServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Events\UserLoggedIn;

class LoginService implements LoginServiceInterface
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function login(string $email, string $password): AccessToken
    {
        $token = $this->authService->authenticate($email, $password);

        $userId = $this->userRepository->getIdByEmail($email);
        if ($userId !== null) {
            UserLoggedIn::dispatch($userId, $email);
        }

        return $token;
    }
}
