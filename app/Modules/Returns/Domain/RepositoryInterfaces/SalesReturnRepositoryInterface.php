<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Modules\Returns\Domain\Entities\SalesReturn;

interface SalesReturnRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?SalesReturn;

    public function findAll(string $tenantId): array;

    public function findByCustomer(string $tenantId, string $customerId): array;

    public function findByStatus(string $tenantId, string $status): array;

    public function findByReference(string $tenantId, string $reference): ?SalesReturn;

    public function save(SalesReturn $return): void;

    public function delete(string $tenantId, string $id): void;
}
