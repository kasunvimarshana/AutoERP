<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'driver_id' => $this->resource->driverId,
            'transaction_id' => $this->resource->transactionId,
            'commission_amount' => $this->resource->commissionAmount,
            'commission_rate' => $this->resource->commissionRate,
            'status' => $this->resource->status,
            'paid_date' => $this->resource->paidDate?->format('Y-m-d'),
        ];
    }
}
