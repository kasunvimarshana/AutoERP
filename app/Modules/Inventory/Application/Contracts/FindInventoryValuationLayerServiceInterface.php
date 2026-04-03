<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindInventoryValuationLayerServiceInterface extends ReadServiceInterface
{
    public function findOpenLayers(int $tenantId, int $productId, string $valuationMethod): Collection;
    public function findByProduct(int $tenantId, int $productId): Collection;
}
