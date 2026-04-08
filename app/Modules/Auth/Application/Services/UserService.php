<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Application\Contracts\UserServiceInterface;
use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Events\UserCreated;
use Modules\Auth\Domain\Events\UserUpdated;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function createUser(string $tenantId, array $data): User
    {
        return DB::transaction(function () use ($tenantId, $data): User {
            $now = now();

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = new User(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                email: $data['email'],
                name: $data['name'],
                role: $data['role'] ?? 'staff',
                status: $data['status'] ?? 'active',
                preferences: $data['preferences'] ?? [],
                createdAt: $now,
                updatedAt: $now,
            );

            $this->userRepository->save($user);

            if (isset($data['password'])) {
                $this->userRepository->updatePassword($tenantId, $user->id, $data['password']);
            }

            $saved = $this->userRepository->findById($tenantId, $user->id);

            if ($saved === null) {
                throw new NotFoundException("User could not be retrieved after creation.");
            }

            Event::dispatch(new UserCreated($saved));

            return $saved;
        });
    }

    public function updateUser(string $tenantId, string $id, array $data): User
    {
        return DB::transaction(function () use ($tenantId, $id, $data): User {
            $existing = $this->userRepository->findById($tenantId, $id);

            if ($existing === null) {
                throw new NotFoundException("User with id [{$id}] not found.");
            }

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $updated = new User(
                id: $existing->id,
                tenantId: $existing->tenantId,
                email: $data['email'] ?? $existing->email,
                name: $data['name'] ?? $existing->name,
                role: $data['role'] ?? $existing->role,
                status: $data['status'] ?? $existing->status,
                preferences: $data['preferences'] ?? $existing->preferences,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->userRepository->save($updated);

            if (isset($data['password'])) {
                $this->userRepository->updatePassword($tenantId, $id, $data['password']);
            }

            $saved = $this->userRepository->findById($tenantId, $id);

            if ($saved === null) {
                throw new NotFoundException("User with id [{$id}] not found after update.");
            }

            Event::dispatch(new UserUpdated($saved));

            return $saved;
        });
    }

    public function deleteUser(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            if ($this->userRepository->findById($tenantId, $id) === null) {
                throw new NotFoundException("User with id [{$id}] not found.");
            }

            $this->userRepository->delete($tenantId, $id);
        });
    }

    public function getUser(string $tenantId, string $id): User
    {
        $user = $this->userRepository->findById($tenantId, $id);

        if ($user === null) {
            throw new NotFoundException("User with id [{$id}] not found.");
        }

        return $user;
    }

    public function getUserByEmail(string $tenantId, string $email): ?User
    {
        return $this->userRepository->findByEmail($tenantId, $email);
    }

    public function getAllUsers(string $tenantId): array
    {
        return $this->userRepository->findAll($tenantId);
    }
}
