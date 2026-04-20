<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Supplier\Domain\Entities\SupplierContact;

interface SupplierContactRepositoryInterface extends RepositoryInterface
{
    public function save(SupplierContact $contact): SupplierContact;

    public function clearPrimaryBySupplier(int $tenantId, int $supplierId, ?int $excludeId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?SupplierContact;
}
