<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use Modules\Procurement\Domain\Entities\Vendor;

/**
 * Vendor repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class VendorRepository extends AbstractRepository implements VendorRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = Vendor::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }
}
