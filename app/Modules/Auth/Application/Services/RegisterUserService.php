<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Application\Contracts\RegisterUserServiceInterface;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\Auth\Domain\Exceptions\RegistrationFailedException;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\SetUserPasswordServiceInterface;

class RegisterUserService implements RegisterUserServiceInterface
{
    public function __construct(
        private readonly CreateUserServiceInterface $createUserService,
        private readonly SetUserPasswordServiceInterface $setUserPasswordService,
    ) {}

    public function register(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $user = $this->createUserService->execute([
                'tenant_id' => $data['tenant_id'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'active' => true,
            ]);

            $userId = $user->getId();
            if ($userId === null) {
                throw new RegistrationFailedException('Failed to persist user during registration.');
            }

            $this->setUserPasswordService->execute([
                'user_id' => $userId,
                'password' => $data['password'],
            ]);

            UserRegistered::dispatch(
                $userId,
                $user->getEmail()->value(),
                $user->getFirstName(),
                $user->getLastName(),
            );

            return $userId;
        });
    }
}
