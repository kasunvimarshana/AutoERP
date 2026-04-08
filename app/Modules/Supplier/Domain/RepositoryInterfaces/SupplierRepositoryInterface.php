<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;

    public function findByEmail(string $email, int $tenantId): mixed;
}
