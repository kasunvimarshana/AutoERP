<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface RoleServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters): Result;

    public function get(int|string $id): Result;

    /** @param array<string, mixed> $payload */
    public function create(array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function update(int|string $id, array $payload): Result;

    public function delete(int|string $id): Result;
}
