<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindStockReturnLineServiceInterface extends ReadServiceInterface
{
    public function findByReturn(int $tenantId, int $returnId): Collection;
}
