<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Supplier\Domain\Entities\SupplierAddress;

interface SupplierAddressRepositoryInterface extends RepositoryInterface
{
    public function save(SupplierAddress $address): SupplierAddress;

    public function clearDefaultBySupplierAndType(int $tenantId, int $supplierId, string $type, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?SupplierAddress;
}
