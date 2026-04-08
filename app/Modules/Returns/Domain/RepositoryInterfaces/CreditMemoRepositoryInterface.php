<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Returns\Domain\Entities\CreditMemo;

interface CreditMemoRepositoryInterface extends RepositoryInterface
{
    public function save(CreditMemo $memo): CreditMemo;
    public function findByStockReturn(int $tenantId, int $stockReturnId): Collection;
    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
