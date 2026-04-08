<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface BatchLotRepositoryInterface extends RepositoryInterface
{
    public function findByNumber(string $batchNumber, int $productId): mixed;

    public function findByProduct(int $productId): Collection;

    public function findExpiring(int $tenantId, \DateTimeInterface $before): Collection;
}
