<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankReconciliationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'bank_account_id' => $this->resource->getBankAccountId(),
            'period_start' => $this->resource->getPeriodStart()->format('Y-m-d'),
            'period_end' => $this->resource->getPeriodEnd()->format('Y-m-d'),
            'opening_balance' => $this->resource->getOpeningBalance(),
            'closing_balance' => $this->resource->getClosingBalance(),
            'status' => $this->resource->getStatus(),
            'completed_by' => $this->resource->getCompletedBy(),
            'completed_at' => $this->resource->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            'row_version' => $this->resource->getRowVersion(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
