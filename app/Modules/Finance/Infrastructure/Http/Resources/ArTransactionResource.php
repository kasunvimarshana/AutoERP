<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'customer_id' => $this->resource->getCustomerId(),
            'account_id' => $this->resource->getAccountId(),
            'transaction_type' => $this->resource->getTransactionType(),
            'amount' => $this->resource->getAmount(),
            'balance_after' => $this->resource->getBalanceAfter(),
            'transaction_date' => $this->resource->getTransactionDate()->format('Y-m-d'),
            'due_date' => $this->resource->getDueDate()?->format('Y-m-d'),
            'currency_id' => $this->resource->getCurrencyId(),
            'reference_type' => $this->resource->getReferenceType(),
            'reference_id' => $this->resource->getReferenceId(),
            'is_reconciled' => $this->resource->isReconciled(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
