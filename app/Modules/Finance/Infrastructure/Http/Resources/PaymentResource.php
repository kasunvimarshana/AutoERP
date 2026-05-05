<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'payment_number' => $this->resource->getPaymentNumber(),
            'direction' => $this->resource->getDirection(),
            'party_type' => $this->resource->getPartyType(),
            'party_id' => $this->resource->getPartyId(),
            'payment_method_id' => $this->resource->getPaymentMethodId(),
            'account_id' => $this->resource->getAccountId(),
            'amount' => $this->resource->getAmount(),
            'currency_id' => $this->resource->getCurrencyId(),
            'exchange_rate' => $this->resource->getExchangeRate(),
            'base_amount' => $this->resource->getBaseAmount(),
            'payment_date' => $this->resource->getPaymentDate()->format('Y-m-d'),
            'status' => $this->resource->getStatus(),
            'reference' => $this->resource->getReference(),
            'notes' => $this->resource->getNotes(),
            'journal_entry_id' => $this->resource->getJournalEntryId(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
