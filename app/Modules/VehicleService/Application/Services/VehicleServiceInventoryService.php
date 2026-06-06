<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Inventory\Application\Services\StockIssuingService;

final class VehicleServiceInventoryService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly StockIssuingService $stockIssuing,
    ) {}

    public function consumeJobParts(object $job): void
    {
        $lines = DB::table('vehicle_service_job_card_lines')
            ->where('tenant_id', $this->support->tenantId())
            ->where('job_card_id', (int) $job->id)
            ->where('requires_stock_movement', true)
            ->whereColumn('consumed_qty', '<', 'quantity')
            ->lockForUpdate()
            ->get();

        if ($lines->isEmpty()) {
            if ($job->inventory_status !== 'consumed') {
                DB::table('vehicle_service_job_cards')->where('id', (int) $job->id)->update([
                    'inventory_status' => 'consumed',
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $result = $this->stockIssuing->issue([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $job->organization_unit_id,
            'source_module' => 'vehicle_service',
            'source_type' => 'vehicle_service_job',
            'source_id' => (int) $job->id,
            'source_reference' => $job->job_card_number,
            'movement_type' => 'SERVICE_CONSUMPTION',
            'warehouse_id' => $job->warehouse_id,
            'lines' => $lines->map(fn (object $line): array => [
                'source_line_id' => (int) $line->id,
                'item_id' => (int) $line->item_id,
                'uom_id' => (int) $line->uom_id,
                'warehouse_id' => (int) ($line->warehouse_id ?? $job->warehouse_id),
                'location_id' => $line->location_id,
                'quantity' => round((float) $line->quantity - (float) $line->consumed_qty, 4),
            ])->all(),
        ]);

        foreach ($lines as $index => $line) {
            $movement = $result['movements'][$index];
            $quantity = round((float) $line->quantity - (float) $line->consumed_qty, 4);
            DB::table('vehicle_service_job_card_lines')->where('id', (int) $line->id)->update([
                'consumed_qty' => $line->quantity,
                'outstanding_qty' => 0,
                'inventory_status' => 'consumed',
                'updated_at' => now(),
            ]);
            DB::table('vehicle_service_job_inventory_links')->insertOrIgnore([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $job->organization_unit_id,
                'job_card_id' => (int) $job->id,
                'job_card_line_id' => (int) $line->id,
                'stock_movement_id' => $movement['movement_id'],
                'movement_type' => 'consume',
                'quantity' => $quantity,
                'quantity_base' => $movement['base_quantity'],
                'unit_cost' => $movement['unit_cost'] ?? 0,
                'total_cost' => $movement['total_cost'],
                'status' => 'posted',
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('vehicle_service_job_cards')->where('id', (int) $job->id)->update([
            'inventory_status' => 'consumed',
            'updated_at' => now(),
        ]);
    }
}
