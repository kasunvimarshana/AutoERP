<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers;

use Modules\Core\Application\Results\Result;

interface DeleteInventoryCostLayerServiceInterface
{
    public function execute(int|string $id): Result;
}