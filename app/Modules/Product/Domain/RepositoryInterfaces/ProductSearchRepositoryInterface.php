<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductSearchRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function searchCatalog(array $criteria): LengthAwarePaginator;
}
