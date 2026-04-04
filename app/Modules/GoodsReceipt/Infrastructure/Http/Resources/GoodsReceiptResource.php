<?php
namespace Modules\GoodsReceipt\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;

class GoodsReceiptResource extends JsonResource
{
    public function __construct(private readonly GoodsReceipt $gr)
    {
        parent::__construct($gr);
    }

    public function toArray($request): array
    {
        return [
            'id'                 => $this->gr->id,
            'tenant_id'          => $this->gr->tenantId,
            'warehouse_id'       => $this->gr->warehouseId,
            'gr_number'          => $this->gr->grNumber,
            'status'             => $this->gr->status,
            'purchase_order_id'  => $this->gr->purchaseOrderId,
            'supplier_id'        => $this->gr->supplierId,
            'supplier_reference' => $this->gr->supplierReference,
            'notes'              => $this->gr->notes,
            'received_at'        => $this->gr->receivedAt?->format('Y-m-d\TH:i:s\Z'),
            'received_by'        => $this->gr->receivedBy,
            'inspected_by'       => $this->gr->inspectedBy,
            'inspected_at'       => $this->gr->inspectedAt?->format('Y-m-d\TH:i:s\Z'),
            'put_away_by'        => $this->gr->putAwayBy,
            'put_away_at'        => $this->gr->putAwayAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
