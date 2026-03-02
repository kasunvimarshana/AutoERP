<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Vendor repository contract.
 *
 * Extends the base repository contract with vendor-specific query methods.
 */
interface VendorRepositoryContract extends RepositoryContract
{
    /**
     * Find all active vendors (tenant-scoped).
     */
    public function findActive(): Collection;
}
