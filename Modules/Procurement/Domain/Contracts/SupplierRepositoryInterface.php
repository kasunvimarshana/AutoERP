<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Contracts;

use Modules\Procurement\Domain\Entities\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Supplier;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Supplier $supplier): Supplier;

    public function delete(int $id, int $tenantId): void;
}
