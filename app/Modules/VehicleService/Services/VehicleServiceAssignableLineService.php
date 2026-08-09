<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceAssignableLineService
{
    public function __construct(
        private readonly VehicleServiceCommissionPolicyService $commissionPolicies,
        private readonly DecimalMath $math,
    ) {}

    /** @return Collection<int, VehicleServiceJobLine> */
    public function forJob(VehicleServiceJob $job): Collection
    {
        $lines = $job->lines()
            ->where('is_employee_assignable', true)
            ->with(['parent', 'item', 'variant', 'uom', 'employeeAssignments.employee'])
            ->get();
        $itemIds = $lines->pluck('item_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
        $defaults = $this->commissionPolicies->laborDefaultsForItems(
            (int) $job->tenant_id,
            (int) $job->organization_unit_id,
            $itemIds,
        );

        foreach ($lines as $line) {
            $comboLabour = $line->line_source_type === VehicleServiceLineSourceType::ComboChild
                && $line->item?->item_type === ItemType::Labour;
            $line->setAttribute('commission_default', $comboLabour
                ? [
                    'commission_type' => 'fixed',
                    'commission_value' => $this->math->mul((string) $line->quantity, (string) $line->unit_cost),
                    'locked' => true,
                ]
                : ($defaults[(int) $line->item_id] ?? null));
        }

        return $lines;
    }
}
