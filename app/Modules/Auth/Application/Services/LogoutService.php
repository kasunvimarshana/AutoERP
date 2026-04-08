<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\LogoutServiceInterface;
use Modules\Auth\Domain\Events\UserLoggedOut;

class LogoutService implements LogoutServiceInterface
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function logout(int $userId): bool
    {
        $result = $this->authService->invalidate($userId);

        if ($result) {
            $email = $this->userRepository->getEmailById($userId);
            if ($email !== null) {
                UserLoggedOut::dispatch($userId, $email);
            }
        }

        return $result;
    }
}
