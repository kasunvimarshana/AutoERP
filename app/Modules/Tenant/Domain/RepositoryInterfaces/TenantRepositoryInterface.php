<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Tenant\Domain\Entities\Tenant;

interface TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    /** @return Tenant[] */
    public function findAll(): array;

    public function save(Tenant $tenant): Tenant;

    public function delete(string $id): void;
}
