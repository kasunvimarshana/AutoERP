<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Returns\Domain\Entities\StockReturn;

interface StockReturnRepositoryInterface extends RepositoryInterface
{
    public function save(StockReturn $return): StockReturn;
    public function findByReferenceNumber(int $tenantId, string $ref): ?StockReturn;
    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
