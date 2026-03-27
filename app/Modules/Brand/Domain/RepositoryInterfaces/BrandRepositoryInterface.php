<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface BrandRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(int $tenantId, string $slug): ?Brand;

    public function findByTenant(int $tenantId): Collection;

    public function save(Brand $brand): Brand;
}
