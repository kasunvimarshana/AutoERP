<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Rules\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Domain\Contracts\AllocationRuleInterface;

final class SkipZeroAvailableRule implements AllocationRuleInterface
{
    public function apply(Collection $candidates, AllocationRequest $request): Collection
    {
        return $candidates
            ->filter(static fn ($candidate): bool => (float) ($candidate->available_quantity ?? 0) > 0)
            ->values();
    }
}
