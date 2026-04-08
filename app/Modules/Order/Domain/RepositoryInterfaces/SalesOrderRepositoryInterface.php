<?php

declare(strict_types=1);

namespace Modules\Order\Domain\RepositoryInterfaces;

use Modules\Order\Domain\Entities\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?SalesOrder;

    /** @return SalesOrder[] */
    public function findAll(string $tenantId): array;

    /** @return SalesOrder[] */
    public function findByCustomer(string $tenantId, string $customerId): array;

    /** @return SalesOrder[] */
    public function findByStatus(string $tenantId, string $status): array;

    public function findByReference(string $tenantId, string $reference): ?SalesOrder;

    public function save(SalesOrder $salesOrder): void;

    public function delete(string $tenantId, string $id): void;
}
