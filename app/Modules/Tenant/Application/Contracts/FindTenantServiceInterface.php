<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Domain\Entities\Tenant;

/**
 * Contract for tenant read queries.
 *
 * Exposes find operations (including by domain) through the service layer so
 * that controllers do not inject the repository directly (DIP compliance).
 */
interface FindTenantServiceInterface
{
    public function find(int $id): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage, int $page, ?string $sort = null, ?string $include = null): LengthAwarePaginator;
}
