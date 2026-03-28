<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function findByCode(int $tenantId, string $code): ?Supplier;

    public function findByTenant(int $tenantId): Collection;

    public function findByUserId(int $tenantId, int $userId): ?Supplier;

    public function save(Supplier $supplier): Supplier;
}
