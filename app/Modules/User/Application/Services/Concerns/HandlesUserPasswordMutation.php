<?php

declare(strict_types=1);

namespace Modules\User\Application\Services\Concerns;

use Illuminate\Support\Facades\Hash;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

trait HandlesUserPasswordMutation
{
    abstract protected function userRepository(): UserRepositoryInterface;

    protected function findUserOrFail(int $userId): User
    {
        $user = $this->userRepository()->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        return $user;
    }

    protected function persistPassword(int $userId, string $password): void
    {
        $this->userRepository()->changePassword($userId, Hash::make($password));
    }
}
