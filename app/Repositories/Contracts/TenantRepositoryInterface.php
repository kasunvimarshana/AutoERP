<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\Tenant;

interface TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    public function create(array $data): Tenant;

    public function update(string $id, array $data): Tenant;
}
