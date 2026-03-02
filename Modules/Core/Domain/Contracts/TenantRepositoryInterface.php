<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Contracts;

use Modules\Core\Domain\Entities\Tenant;
use Modules\Core\Domain\ValueObjects\TenantId;

interface TenantRepositoryInterface
{
    public function findById(TenantId $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    /**
     * @return Tenant[]
     */
    public function all(): array;

    public function save(Tenant $tenant): Tenant;

    public function delete(TenantId $id): void;
}
