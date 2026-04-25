<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface SearchProductCatalogServiceInterface
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function execute(array $criteria): array;
}
