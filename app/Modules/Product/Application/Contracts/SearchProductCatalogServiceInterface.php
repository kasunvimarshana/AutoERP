<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface SearchProductCatalogServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function execute(array $data = []): array;
}
