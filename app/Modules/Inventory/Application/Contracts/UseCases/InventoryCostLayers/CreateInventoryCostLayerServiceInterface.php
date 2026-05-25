<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers;

use Modules\Core\Application\Results\Result;

interface CreateInventoryCostLayerServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}