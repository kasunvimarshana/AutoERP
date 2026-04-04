<?php

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Returns\Domain\Entities\StockReturn;

class StockReturnResource extends JsonResource
{
    public function __construct(private readonly StockReturn $return)
    {
        parent::__construct($return);
    }

    public function toArray($request): array
    {
        return [
            'id'                   => $this->return->id,
            'tenant_id'            => $this->return->tenantId,
            'warehouse_id'         => $this->return->warehouseId,
            'return_number'        => $this->return->returnNumber,
            'return_type'          => $this->return->returnType,
            'status'               => $this->return->status,
            'original_order_id'    => $this->return->originalOrderId,
            'original_order_type'  => $this->return->originalOrderType,
            'customer_id'          => $this->return->customerId,
            'supplier_id'          => $this->return->supplierId,
            'reason'               => $this->return->reason,
            'total_amount'         => $this->return->totalAmount,
            'restocking_fee'       => $this->return->restockingFee,
            'credit_memo_number'   => $this->return->creditMemoNumber,
            'notes'                => $this->return->notes,
            'approved_by'          => $this->return->approvedBy,
            'approved_at'          => $this->return->approvedAt?->format('Y-m-d\TH:i:s\Z'),
            'completed_by'         => $this->return->completedBy,
            'completed_at'         => $this->return->completedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
