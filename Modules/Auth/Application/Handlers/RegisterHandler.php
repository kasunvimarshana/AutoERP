<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use Modules\Auth\Application\Commands\RegisterCommand;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\Entities\User as UserEntity;
use Modules\Auth\Domain\ValueObjects\Email;
use Modules\Auth\Domain\ValueObjects\Password;
use Illuminate\Support\Facades\DB;

class RegisterHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Handle the register command and return the domain User entity.
     */
    public function handle(RegisterCommand $command): UserEntity
    {
        $email    = new Email($command->email);
        $password = Password::fromPlainText($command->password);

        $exists = $this->users->findByEmail($email, $command->tenantId);

        if ($exists !== null) {
            throw new \DomainException("A user with email {$email} already exists in this tenant.");
        }

        return DB::transaction(function () use ($command, $email, $password): UserEntity {
            $user = new UserEntity(
                id: 0,
                tenantId: $command->tenantId,
                organisationId: $command->organisationId,
                name: $command->name,
                email: $email,
                role: $command->role ?? 'user',
                isActive: true,
                passwordHash: $password->getHashedValue(),
            );

            return $this->users->save($user);
        });
    }
}
