<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ComboItem;

interface ComboItemRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, ComboItem> */
    public function findByProduct(int $productId): Collection;

    public function save(ComboItem $comboItem): ComboItem;
}
