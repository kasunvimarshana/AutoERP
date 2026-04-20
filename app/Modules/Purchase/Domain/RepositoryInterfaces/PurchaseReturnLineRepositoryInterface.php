<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseReturnLine;

interface PurchaseReturnLineRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseReturnLine $line): PurchaseReturnLine;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseReturnLine;
}
