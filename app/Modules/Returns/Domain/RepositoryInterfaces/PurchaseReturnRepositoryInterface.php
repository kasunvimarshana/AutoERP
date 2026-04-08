<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Modules\Returns\Domain\Entities\PurchaseReturn;

interface PurchaseReturnRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PurchaseReturn;

    public function findAll(string $tenantId): array;

    public function findBySupplier(string $tenantId, string $supplierId): array;

    public function findByStatus(string $tenantId, string $status): array;

    public function findByReference(string $tenantId, string $reference): ?PurchaseReturn;

    public function save(PurchaseReturn $return): void;

    public function delete(string $tenantId, string $id): void;
}
