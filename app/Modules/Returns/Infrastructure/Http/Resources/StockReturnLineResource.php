<?php

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Returns\Domain\Entities\StockReturnLine;

class StockReturnLineResource extends JsonResource
{
    public function __construct(private readonly StockReturnLine $line)
    {
        parent::__construct($line);
    }

    public function toArray($request): array
    {
        return [
            'id'                     => $this->line->id,
            'stock_return_id'        => $this->line->stockReturnId,
            'product_id'             => $this->line->productId,
            'variant_id'             => $this->line->variantId,
            'return_qty'             => $this->line->returnQty,
            'condition'              => $this->line->condition,
            'quality_check_result'   => $this->line->qualityCheckResult,
            'location_id'            => $this->line->locationId,
            'original_batch_id'      => $this->line->originalBatchId,
            'original_lot_number'    => $this->line->originalLotNumber,
            'original_serial_number' => $this->line->originalSerialNumber,
            'unit_price'             => $this->line->unitPrice,
            'line_total'             => $this->line->lineTotal,
            'restock_action'         => $this->line->restockAction,
            'notes'                  => $this->line->notes,
            'quality_checked_by'     => $this->line->qualityCheckedBy,
            'quality_checked_at'     => $this->line->qualityCheckedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
