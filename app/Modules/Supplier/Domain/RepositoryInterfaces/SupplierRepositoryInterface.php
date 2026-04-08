<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Supplier;

    /** @return Supplier[] */
    public function findAll(string $tenantId): array;

    public function findByCode(string $tenantId, string $code): ?Supplier;

    /** @return Supplier[] */
    public function findActive(string $tenantId): array;

    public function save(Supplier $supplier): void;

    public function delete(string $tenantId, string $id): void;
}
