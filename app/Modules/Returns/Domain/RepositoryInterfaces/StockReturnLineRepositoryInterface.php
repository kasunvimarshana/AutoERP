<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Returns\Domain\Entities\StockReturnLine;

interface StockReturnLineRepositoryInterface extends RepositoryInterface
{
    public function save(StockReturnLine $line): StockReturnLine;
    public function findByReturn(int $tenantId, int $returnId): Collection;
}
