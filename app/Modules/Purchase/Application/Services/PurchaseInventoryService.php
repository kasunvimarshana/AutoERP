<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Collection;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Inventory\Application\Services\StockIssuingService;
use Modules\Inventory\Application\Services\StockReceivingService;

final class PurchaseInventoryService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly StockReceivingService $stockReceiving,
        private readonly StockIssuingService $stockIssuing,
    ) {}

    /** @param Collection<int, object> $lines */
    public function receiveGrn(object $grn, Collection $lines): array
    {
        return $this->stockReceiving->receive([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $grn->organization_unit_id,
            'source_module' => 'purchase',
            'source_type' => 'purchase_grn',
            'source_id' => (int) $grn->id,
            'source_reference' => $grn->grn_number,
            'warehouse_id' => (int) $grn->warehouse_id,
            'lines' => $lines->map(fn (object $line): array => [
                'source_line_id' => (int) $line->id,
                'item_id' => (int) $line->item_id,
                'uom_id' => (int) $line->uom_id,
                'warehouse_id' => (int) ($line->warehouse_id ?? $grn->warehouse_id),
                'location_id' => $line->location_id,
                'variant_id' => $line->variant_id,
                'batch_id' => $line->batch_id,
                'serial_id' => $line->serial_id,
                'quantity' => (float) $line->accepted_qty,
                'unit_cost' => (float) $line->unit_price,
            ])->all(),
        ]);
    }

    /** @param Collection<int, object> $lines */
    public function issuePurchaseReturn(object $return, Collection $lines): array
    {
        return $this->stockIssuing->issue([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $return->organization_unit_id,
            'source_module' => 'purchase',
            'source_type' => 'purchase_return',
            'source_id' => (int) $return->id,
            'source_reference' => $return->return_number,
            'lines' => $lines->map(fn (object $line): array => [
                'source_line_id' => (int) $line->id,
                'item_id' => (int) $line->item_id,
                'uom_id' => (int) $line->uom_id,
                'warehouse_id' => (int) $line->warehouse_id,
                'location_id' => $line->location_id,
                'variant_id' => $line->variant_id,
                'batch_id' => $line->batch_id,
                'serial_id' => $line->serial_id,
                'quantity' => (float) $line->return_qty,
            ])->all(),
        ]);
    }
}
