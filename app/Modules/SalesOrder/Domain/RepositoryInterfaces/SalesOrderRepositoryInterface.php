<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface SalesOrderRepositoryInterface extends RepositoryInterface
{
    public function save(SalesOrder $order): SalesOrder;
    public function findByCustomer(int $tenantId, int $customerId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?SalesOrder;
}
