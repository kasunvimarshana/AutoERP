<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WebhookDispatcher       $webhookDispatcher,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    public function getUser(int $id, int $tenantId): ?User
    {
        return $this->userRepository->findById($id, $tenantId);
    }

    public function getUserByEmail(string $email, int $tenantId): ?User
    {
        return $this->userRepository->findByEmail($email, $tenantId);
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function listUsers(
        int $tenantId,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator {
        return $this->userRepository->paginate($tenantId, $perPage, $filters, $sortBy, $sortDir, $search);
    }

    /*
    |--------------------------------------------------------------------------
    | Write Operations (ACID transactions)
    |--------------------------------------------------------------------------
    */

    public function createUser(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $existing = $this->userRepository->findByEmail($dto->email, (int) $dto->tenantId);

            if ($existing) {
                throw new \DomainException("A user with email '{$dto->email}' already exists in this tenant.");
            }

            $user = $this->userRepository->create($dto);

            event(new UserCreated($user));

            $this->webhookDispatcher->dispatch('user.created', (string) $user->tenant_id, $user->toArray());

            Log::info('User created', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

            return $user;
        });
    }

    public function updateUser(int $id, int $tenantId, UserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $tenantId, $dto): User {
            $before = $this->userRepository->findById($id, $tenantId);

            if (! $before) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User {$id} not found.");
            }

            // Guard duplicate email across same tenant
            if ($dto->email !== $before->email) {
                $collision = $this->userRepository->findByEmail($dto->email, $tenantId);
                if ($collision && $collision->id !== $id) {
                    throw new \DomainException("Email '{$dto->email}' is already used by another user.");
                }
            }

            $user    = $this->userRepository->update($id, $tenantId, $dto);
            $changes = $user->getChanges();

            event(new UserUpdated($user, $changes));

            $this->webhookDispatcher->dispatch('user.updated', (string) $tenantId, [
                'user'    => $user->toArray(),
                'changes' => $changes,
            ]);

            Log::info('User updated', ['user_id' => $id, 'tenant_id' => $tenantId, 'changes' => array_keys($changes)]);

            return $user;
        });
    }

    public function deleteUser(int $id, int $tenantId): bool
    {
        return DB::transaction(function () use ($id, $tenantId): bool {
            $user = $this->userRepository->findById($id, $tenantId);

            if (! $user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User {$id} not found.");
            }

            $email = $user->email;

            $deleted = $this->userRepository->delete($id, $tenantId);

            event(new UserDeleted($id, $tenantId, $email));

            $this->webhookDispatcher->dispatch('user.deleted', (string) $tenantId, [
                'id'    => $id,
                'email' => $email,
            ]);

            Log::info('User deleted', ['user_id' => $id, 'tenant_id' => $tenantId]);

            return $deleted;
        });
    }

    public function restoreUser(int $id, int $tenantId): bool
    {
        return DB::transaction(function () use ($id, $tenantId): bool {
            return $this->userRepository->restore($id, $tenantId);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Stats
    |--------------------------------------------------------------------------
    */

    public function countUsersInTenant(int $tenantId): int
    {
        return $this->userRepository->countByTenant($tenantId);
    }
}
