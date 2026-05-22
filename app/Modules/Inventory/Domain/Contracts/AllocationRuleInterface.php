<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;

interface AllocationRuleInterface
{
    public function apply(Collection $candidates, AllocationRequest $request): Collection;
}
