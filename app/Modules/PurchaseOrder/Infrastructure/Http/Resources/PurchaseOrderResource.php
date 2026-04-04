<?php
namespace Modules\PurchaseOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;

class PurchaseOrderResource extends JsonResource
{
    public function __construct(private readonly PurchaseOrder $po)
    {
        parent::__construct($po);
    }

    public function toArray($request): array
    {
        return [
            'id'                     => $this->po->id,
            'tenant_id'              => $this->po->tenantId,
            'warehouse_id'           => $this->po->warehouseId,
            'supplier_id'            => $this->po->supplierId,
            'po_number'              => $this->po->poNumber,
            'status'                 => $this->po->status,
            'total_amount'           => $this->po->totalAmount,
            'tax_amount'             => $this->po->taxAmount,
            'currency'               => $this->po->currency,
            'notes'                  => $this->po->notes,
            'expected_delivery_date' => $this->po->expectedDeliveryDate?->format('Y-m-d'),
            'approved_at'            => $this->po->approvedAt?->format('Y-m-d\TH:i:s\Z'),
            'approved_by'            => $this->po->approvedBy,
        ];
    }
}
