<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'payment_id' => $this->resource->getPaymentId(),
            'invoice_type' => $this->resource->getInvoiceType(),
            'invoice_id' => $this->resource->getInvoiceId(),
            'allocated_amount' => $this->resource->getAllocatedAmount(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
