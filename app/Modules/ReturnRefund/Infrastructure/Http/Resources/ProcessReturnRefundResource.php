<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessReturnRefundResource extends JsonResource
{
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'inspection_id' => $this->resource->inspectionId,
            'refund_id' => $this->resource->refundId,
            'refund_number' => $this->resource->refundNumber,
            'gross_amount' => $this->resource->grossAmount,
            'adjustment_amount' => $this->resource->adjustmentAmount,
            'net_refund_amount' => $this->resource->netRefundAmount,
            'status' => $this->resource->status,
        ];
    }
}
