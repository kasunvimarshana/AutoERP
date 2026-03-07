<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Retrieve a paginated list of users with optional filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find a user by primary key.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by their Keycloak subject ID.
     */
    public function findByKeycloakId(string $keycloakId): ?User;

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user record.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user by ID.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): ?User;

    /**
     * Soft-delete a user.
     */
    public function delete(int $id): bool;

    /**
     * Full-text search across name, email, username, and department.
     *
     * @return Collection<int, User>
     */
    public function search(string $term, int $limit = 15): Collection;

    /**
     * Create or update a user matched by keycloak_id.
     *
     * @param  array<string, mixed> $data
     */
    public function upsertByKeycloakId(string $keycloakId, array $data): User;
}
