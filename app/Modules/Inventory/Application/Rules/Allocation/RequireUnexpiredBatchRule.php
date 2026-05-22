<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Rules\Allocation;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Domain\Contracts\AllocationRuleInterface;

final class RequireUnexpiredBatchRule implements AllocationRuleInterface
{
    public function apply(Collection $candidates, AllocationRequest $request): Collection
    {
        if (($request->ruleContext['allow_expired'] ?? false) === true) {
            return $candidates->values();
        }

        $today = CarbonImmutable::today()->toDateString();

        return $candidates
            ->filter(static function ($candidate) use ($today): bool {
                if (empty($candidate->expiry_date)) {
                    return true;
                }

                return (string) $candidate->expiry_date >= $today;
            })
            ->values();
    }
}
