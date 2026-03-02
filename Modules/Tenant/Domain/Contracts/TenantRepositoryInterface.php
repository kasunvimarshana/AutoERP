<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Contracts;

use Modules\Tenant\Domain\Entities\Tenant;

interface TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    public function save(Tenant $tenant): Tenant;

    public function delete(int $id): void;

    public function findAll(int $page = 1, int $perPage = 25): array;
}
