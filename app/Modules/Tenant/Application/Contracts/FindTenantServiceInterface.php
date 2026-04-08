<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

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
}
