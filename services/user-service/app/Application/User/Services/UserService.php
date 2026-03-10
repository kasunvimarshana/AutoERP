<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Infrastructure\Persistence\Repositories\UserProfileRepository;
use App\Infrastructure\Messaging\EventPublisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * UserService
 *
 * Handles user profile CRUD, role assignment, and preference management.
 * Authentication is handled by the Auth Service; this service manages profiles.
 */
class UserService
{
    public function __construct(
        private readonly UserProfileRepository $repository,
        private readonly EventPublisher        $eventPublisher,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    public function list(
        string $tenantId,
        array  $filters = [],
        int    $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->listForTenant($tenantId, $filters, $perPage);
    }

    public function findById(string $id, string $tenantId): \App\Infrastructure\Persistence\Models\UserProfile
    {
        $user = $this->repository->findBy(['id' => $id, 'tenant_id' => $tenantId]);

        if ($user === null) {
            throw new \RuntimeException("User [{$id}] not found.", 404);
        }

        return $user;
    }

    public function findByAuthUserId(string $authUserId, string $tenantId): ?\App\Infrastructure\Persistence\Models\UserProfile
    {
        return $this->repository->findByAuthUserId($authUserId, $tenantId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Commands
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create or update a user profile (upsert on auth_user_id).
     * Called when Auth Service broadcasts user.registered events.
     *
     * @param  array<string, mixed> $data
     * @return \App\Infrastructure\Persistence\Models\UserProfile
     */
    public function upsertProfile(array $data): \App\Infrastructure\Persistence\Models\UserProfile
    {
        $profile = $this->repository->updateOrCreate(
            ['auth_user_id' => $data['auth_user_id'], 'tenant_id' => $data['tenant_id']],
            $data
        );

        $this->eventPublisher->publish('kvsaas.events', 'user.profile_updated', [
            'user_id'   => $profile->id,
            'tenant_id' => $data['tenant_id'],
        ]);

        return $profile;
    }

    /**
     * Update user preferences.
     *
     * @param  string               $id
     * @param  string               $tenantId
     * @param  array<string, mixed> $preferences
     * @return \App\Infrastructure\Persistence\Models\UserProfile
     */
    public function updatePreferences(string $id, string $tenantId, array $preferences): \App\Infrastructure\Persistence\Models\UserProfile
    {
        $user = $this->findById($id, $tenantId);

        return $this->repository->update($user->id, [
            'preferences' => array_merge($user->preferences ?? [], $preferences),
        ]);
    }

    /**
     * Assign a role to a user.
     *
     * @param  string $userId
     * @param  string $tenantId
     * @param  string $roleName
     * @return void
     */
    public function assignRole(string $userId, string $tenantId, string $roleName): void
    {
        $user = $this->findById($userId, $tenantId);
        $user->assignRole($roleName);

        Log::info('Role assigned', [
            'user_id'   => $userId,
            'role'      => $roleName,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Delete a user profile (soft delete).
     *
     * @param  string $id
     * @param  string $tenantId
     * @return void
     */
    public function delete(string $id, string $tenantId): void
    {
        $this->findById($id, $tenantId);
        $this->repository->softDelete($id);
    }
}
