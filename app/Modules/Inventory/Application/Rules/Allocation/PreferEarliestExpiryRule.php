<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Rules\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Domain\Contracts\AllocationRuleInterface;

final class PreferEarliestExpiryRule implements AllocationRuleInterface
{
    public function apply(Collection $candidates, AllocationRequest $request): Collection
    {
        return $candidates
            ->sort(static function (object $left, object $right): int {
                $leftEmpty = empty($left->expiry_date);
                $rightEmpty = empty($right->expiry_date);

                if ($leftEmpty !== $rightEmpty) {
                    return $leftEmpty ? 1 : -1;
                }

                $leftExpiry = (string) ($left->expiry_date ?? '9999-12-31');
                $rightExpiry = (string) ($right->expiry_date ?? '9999-12-31');

                if ($leftExpiry !== $rightExpiry) {
                    return $leftExpiry <=> $rightExpiry;
                }

                return ((int) ($left->stock_level_id ?? 0)) <=> ((int) ($right->stock_level_id ?? 0));
            })
            ->values();
    }
}
