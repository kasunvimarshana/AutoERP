<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Application\DTOs\AllocationResultDTO;

interface AllocationStrategyContract
{
    public function allocate(Collection $layers, MovementLineDTO $line): AllocationResultDTO;
}
