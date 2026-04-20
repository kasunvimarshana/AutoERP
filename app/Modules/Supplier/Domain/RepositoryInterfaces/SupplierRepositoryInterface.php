<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function save(Supplier $supplier): Supplier;

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Supplier;

    public function findByTenantAndSupplierCode(int $tenantId, string $supplierCode): ?Supplier;

    public function find(int|string $id, array $columns = ['*']): ?Supplier;
}
