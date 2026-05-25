<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnitTypes;

use Modules\Core\Application\Results\Result;

interface OrganizationUnitTypeServiceInterface
{
    public function listByTenant(int|string $tenantId): Result;

    public function get(int|string $id): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int|string $id, array $payload): Result;

    public function delete(int|string $id): Result;
}