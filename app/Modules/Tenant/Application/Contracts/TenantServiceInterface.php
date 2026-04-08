<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Domain\Entities\Tenant;

interface TenantServiceInterface
{
    public function createTenant(array $data): Tenant;

    public function updateTenant(string $id, array $data): Tenant;

    public function deleteTenant(string $id): void;

    public function getTenant(string $id): Tenant;

    /** @return Tenant[] */
    public function getAllTenants(): array;

    public function getTenantByDomain(string $domain): ?Tenant;
}
