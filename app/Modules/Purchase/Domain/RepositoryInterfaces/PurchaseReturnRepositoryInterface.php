<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseReturn;

interface PurchaseReturnRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseReturn $return): PurchaseReturn;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseReturn;
}
