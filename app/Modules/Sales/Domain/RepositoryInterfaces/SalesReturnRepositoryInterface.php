<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Sales\Domain\Entities\SalesReturn;

interface SalesReturnRepositoryInterface extends RepositoryInterface
{
    public function save(SalesReturn $return): SalesReturn;

    public function findByTenantAndReturnNumber(int $tenantId, string $returnNumber): ?SalesReturn;

    public function find(int|string $id, array $columns = ['*']): ?SalesReturn;
}
