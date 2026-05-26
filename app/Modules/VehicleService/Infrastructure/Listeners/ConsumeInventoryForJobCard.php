<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Services\InventoryTransactionService;
use Modules\VehicleService\Domain\Events\JobCardCompleted;

final class ConsumeInventoryForJobCard
{
    public function __construct(private readonly InventoryTransactionService $inventory)
    {
    }

    public function handle(JobCardCompleted $event): void
    {
        $jobCard = DB::table('vehicle_service_job_cards')->lockForUpdate()->find($event->jobCardId);
        if ($jobCard === null) {
            return;
        }

        foreach (DB::table('vehicle_service_job_card_lines')->where('job_card_id', $event->jobCardId)->get() as $line) {
            $alreadyConsumed = DB::table('stock_movements')
                ->where('reference_type', 'SERVICE_CONSUMPTION')
                ->where('reference_id', (int) $line->id)
                ->exists();

            if ($alreadyConsumed) {
                continue;
            }

            $context = array_merge((array) $line, [
                'tenant_id' => (int) $line->tenant_id,
                'organization_unit_id' => $line->organization_unit_id,
                'warehouse_id' => $line->warehouse_id ?? $jobCard->warehouse_id,
                'reference_type' => 'SERVICE_CONSUMPTION',
                'created_by' => $jobCard->updated_by ?? $jobCard->created_by,
            ]);

            $this->inventory->issue(
                (int) $line->item_id,
                (float) $line->quantity,
                (float) ($line->unit_cost ?? 0),
                (int) ($line->warehouse_id ?? $jobCard->warehouse_id),
                'SERVICE_CONSUMPTION',
                (int) $line->id,
                $context,
            );
        }
    }
}
