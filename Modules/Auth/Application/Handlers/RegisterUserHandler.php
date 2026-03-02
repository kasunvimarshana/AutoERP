<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Commands\RegisterUserCommand;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Enums\UserStatus;

class RegisterUserHandler extends BaseHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(RegisterUserCommand $command): User
    {
        return $this->transaction(function () use ($command): User {
            $existingUser = $this->userRepository->findByEmail($command->email, $command->tenantId);

            if ($existingUser !== null) {
                throw new \DomainException('A user with this email already exists in this tenant.');
            }

            $user = new User(
                id: null,
                tenantId: $command->tenantId,
                name: $command->name,
                email: $command->email,
                passwordHash: Hash::make($command->password),
                status: UserStatus::Active->value,
                createdAt: null,
                updatedAt: null,
            );

            return $this->userRepository->save($user);
        });
    }
}
