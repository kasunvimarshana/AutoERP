<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Shared\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * OrderRepositoryInterface
 */
interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * List orders for a tenant with optional filters.
     *
     * @param  string                $tenantId
     * @param  array<string, mixed>  $filters
     * @param  int                   $perPage
     * @return LengthAwarePaginator
     */
    public function listForTenant(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15
    ): LengthAwarePaginator;
}
