<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function authenticate(string $email, string $password): AccessToken
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new InvalidCredentialsException;
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->tokenService->issueToken($user->getAuthIdentifier());
    }

    public function invalidate(int $userId): bool
    {
        return $this->tokenService->revokeCurrentToken($userId);
    }
}
