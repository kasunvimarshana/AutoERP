<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Sales\Domain\Entities\SalesOrder;

interface SalesOrderRepositoryInterface extends RepositoryInterface
{
    public function save(SalesOrder $order): SalesOrder;

    public function findByTenantAndSoNumber(int $tenantId, string $soNumber): ?SalesOrder;

    public function find(int|string $id, array $columns = ['*']): ?SalesOrder;
}
