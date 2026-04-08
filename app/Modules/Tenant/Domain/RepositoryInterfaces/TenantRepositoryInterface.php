<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;

interface TenantRepositoryInterface extends RepositoryInterface
{
    public function findByDomain(string $domain): ?Tenant;

    public function save(Tenant $tenant): Tenant;
}
