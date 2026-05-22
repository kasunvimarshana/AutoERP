<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\AllocationResult;

interface AllocationStrategyInterface
{
    public function allocate(Collection $candidates, AllocationRequest $request): AllocationResult;
}
