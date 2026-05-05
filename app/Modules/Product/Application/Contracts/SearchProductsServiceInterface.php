<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface SearchProductsServiceInterface
{
    public function execute(array $filters = []): LengthAwarePaginator;
}
