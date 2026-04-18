<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\LogoutServiceInterface;
use Modules\Auth\Domain\Events\UserLoggedOut;

class LogoutService implements LogoutServiceInterface
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
    ) {}

    public function logout(int $userId): bool
    {
        $result = $this->authService->invalidate($userId);

        if ($result) {
            $email = Auth::user()?->email;
            if ($email !== null) {
                UserLoggedOut::dispatch($userId, $email);
            }
        }

        return $result;
    }
}
