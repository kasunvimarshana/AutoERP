<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\LoginServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Events\UserLoggedIn;

class LoginService implements LoginServiceInterface
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
    ) {}

    public function login(string $email, string $password): AccessToken
    {
        $token = $this->authService->authenticate($email, $password);

        $user = Auth::user();
        $userId = $user ? (int) $user->getAuthIdentifier() : null;
        $userEmail = $user?->email ?? $email;

        if ($userId !== null && $userId > 0) {
            UserLoggedIn::dispatch($userId, (string) $userEmail);
        }

        return $token;
    }
}
