<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface UserServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters): Result;

    public function get(int|string $id): Result;

    /** @param array<string, mixed> $payload */
    public function create(array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function update(int|string $id, array $payload): Result;

    public function activate(int|string $id): Result;

    public function deactivate(int|string $id): Result;

    public function suspend(int|string $id): Result;

    /** @param array<string, mixed> $payload */
    public function assignUserToOrganizationUnit(int|string $id, array $payload): Result;

    public function removeUserFromOrganizationUnit(int|string $id, int|string $organizationUnitId): Result;

    public function resolveByIdentity(string $providerKey, string $providerUserKey): Result;

    public function delete(int|string $id): Result;
}
