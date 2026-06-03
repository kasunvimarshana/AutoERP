<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\InventoryEngines;

use Modules\Core\Application\Results\Result;

interface InventoryEnginePolicyInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function inspect(array $context): Result;
}
