<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface SerialNumberRepositoryInterface extends RepositoryInterface
{
    public function findBySerial(string $serialNumber, int $productId): mixed;

    public function findByProduct(int $productId): Collection;

    public function findAvailableByProduct(int $productId): Collection;
}
