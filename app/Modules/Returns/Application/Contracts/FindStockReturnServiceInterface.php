<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindStockReturnServiceInterface extends ReadServiceInterface
{
    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
