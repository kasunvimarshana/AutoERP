<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface InventoryValuationServiceInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function value(array $payload): Result;
}
