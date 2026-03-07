<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Return a paginated, filtered list of users.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAllUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->getAll($filters);
    }

    /**
     * Fetch a single user by ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Fetch a user by their Keycloak subject ID.
     */
    public function getUserByKeycloakId(string $keycloakId): ?User
    {
        return $this->userRepository->findByKeycloakId($keycloakId);
    }

    /**
     * Create a new user and optionally register them in Keycloak.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            // If no keycloak_id supplied, attempt to create the user in Keycloak first
            if (empty($data['keycloak_id'])) {
                $keycloakId = $this->createKeycloakUser($data);

                if ($keycloakId !== null) {
                    $data['keycloak_id'] = $keycloakId;
                }
            }

            $data['roles'] = $data['roles'] ?? [];

            $user = $this->userRepository->create($data);

            event(new UserCreated($user));

            Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);

            return $user;
        });
    }

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function updateUser(int $id, array $data): ?User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return null;
        }

        return DB::transaction(function () use ($id, $data, $user): User {
            $originalData = $user->toArray();

            $updated = $this->userRepository->update($id, $data);

            event(new UserUpdated($updated, $originalData));

            Log::info('User updated', ['user_id' => $id]);

            return $updated;
        });
    }

    /**
     * Soft-delete a user.
     *
     * @throws Throwable
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return false;
        }

        return DB::transaction(function () use ($id, $user): bool {
            $userData = $user->toArray();

            $deleted = $this->userRepository->delete($id);

            if ($deleted) {
                event(new UserDeleted($id, $userData));

                Log::info('User deleted', ['user_id' => $id]);
            }

            return $deleted;
        });
    }

    /**
     * Create or update a local user record from Keycloak JWT claims.
     * Called automatically by the KeycloakAuth middleware on every request.
     *
     * @param  array<string, mixed> $data  Keycloak claims mapped to user fields
     */
    public function syncFromKeycloak(string $keycloakId, array $data): User
    {
        $user = $this->userRepository->upsertByKeycloakId($keycloakId, $data);

        Log::debug('User synced from Keycloak', ['keycloak_id' => $keycloakId, 'user_id' => $user->id]);

        return $user;
    }

    /**
     * Assign a role to a user.
     *
     * @throws Throwable
     */
    public function assignRole(int $id, string $role): ?User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return null;
        }

        $roles = $user->roles ?? [];

        if (! in_array($role, $roles, true)) {
            $roles[] = $role;

            return DB::transaction(function () use ($id, $roles, $user): User {
                $originalData = $user->toArray();
                $updated      = $this->userRepository->update($id, ['roles' => $roles]);

                event(new UserUpdated($updated, $originalData));

                Log::info('Role assigned to user', ['user_id' => $id, 'role' => implode(',', $roles)]);

                return $updated;
            });
        }

        return $user;
    }

    /**
     * Revoke a role from a user.
     *
     * @throws Throwable
     */
    public function revokeRole(int $id, string $role): ?User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return null;
        }

        $roles = array_values(array_filter($user->roles ?? [], fn (string $r): bool => $r !== $role));

        return DB::transaction(function () use ($id, $roles, $user): User {
            $originalData = $user->toArray();
            $updated      = $this->userRepository->update($id, ['roles' => $roles]);

            event(new UserUpdated($updated, $originalData));

            Log::info('Role revoked from user', ['user_id' => $id, 'role' => implode(',', $roles)]);

            return $updated;
        });
    }

    /**
     * Attempt to create a user in Keycloak via the Admin REST API.
     * Returns the Keycloak user UUID on success, or null if the operation fails
     * (so the local record can still be created without a Keycloak link).
     *
     * @param  array<string, mixed> $data
     */
    private function createKeycloakUser(array $data): ?string
    {
        try {
            $token = $this->getKeycloakAdminToken();

            if ($token === null) {
                return null;
            }

            $baseUrl = config('keycloak.base_url');
            $realm   = config('keycloak.realm');

            $response = Http::withToken($token)
                ->timeout(10)
                ->post("{$baseUrl}/admin/realms/{$realm}/users", [
                    'username'  => $data['username'] ?? $data['email'],
                    'email'     => $data['email'],
                    'firstName' => $data['first_name'] ?? '',
                    'lastName'  => $data['last_name'] ?? '',
                    'enabled'   => $data['is_active'] ?? true,
                ]);

            if ($response->status() === 201) {
                // Keycloak returns the new user Location header
                $location = $response->header('Location');
                return $location ? basename($location) : null;
            }

            Log::warning('Keycloak user creation failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::warning('Keycloak user creation threw an exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Obtain a short-lived admin access token from Keycloak.
     */
    private function getKeycloakAdminToken(): ?string
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(config('keycloak.token_url'), [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => config('keycloak.client_id'),
                    'client_secret' => config('keycloak.client_secret'),
                ]);

            if ($response->failed()) {
                Log::warning('Failed to obtain Keycloak admin token', ['status' => $response->status()]);

                return null;
            }

            return $response->json('access_token');
        } catch (Throwable $e) {
            Log::warning('Exception while obtaining Keycloak admin token', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
