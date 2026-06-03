<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface InventoryAllocationServiceInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function allocate(array $payload): Result;
}
