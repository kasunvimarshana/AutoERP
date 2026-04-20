<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\TransferOrderLine;

class TransferOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'from_warehouse_id' => $this->getFromWarehouseId(),
            'to_warehouse_id' => $this->getToWarehouseId(),
            'transfer_number' => $this->getTransferNumber(),
            'status' => $this->getStatus(),
            'request_date' => $this->getRequestDate(),
            'expected_date' => $this->getExpectedDate(),
            'shipped_date' => $this->getShippedDate(),
            'received_date' => $this->getReceivedDate(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'lines' => array_map(static fn (TransferOrderLine $line): array => [
                'id' => $line->getId(),
                'tenant_id' => $line->getTenantId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'from_location_id' => $line->getFromLocationId(),
                'to_location_id' => $line->getToLocationId(),
                'uom_id' => $line->getUomId(),
                'requested_qty' => $line->getRequestedQty(),
                'shipped_qty' => $line->getShippedQty(),
                'received_qty' => $line->getReceivedQty(),
                'unit_cost' => $line->getUnitCost(),
            ], $this->getLines()),
        ];
    }
}
