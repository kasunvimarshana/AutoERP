<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts;

use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierServiceInterface
{
    public function getSupplier(string $tenantId, string $id): Supplier;

    /** @return Supplier[] */
    public function getAllSuppliers(string $tenantId): array;

    public function createSupplier(string $tenantId, array $data): Supplier;

    public function updateSupplier(string $tenantId, string $id, array $data): Supplier;

    public function deleteSupplier(string $tenantId, string $id): void;
}
