<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditMemoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'party_id' => $this->resource->getPartyId(),
            'party_type' => $this->resource->getPartyType(),
            'credit_memo_number' => $this->resource->getCreditMemoNumber(),
            'amount' => $this->resource->getAmount(),
            'issued_date' => $this->resource->getIssuedDate()->format('Y-m-d'),
            'status' => $this->resource->getStatus(),
            'return_order_id' => $this->resource->getReturnOrderId(),
            'return_order_type' => $this->resource->getReturnOrderType(),
            'applied_to_invoice_id' => $this->resource->getAppliedToInvoiceId(),
            'applied_to_invoice_type' => $this->resource->getAppliedToInvoiceType(),
            'notes' => $this->resource->getNotes(),
            'journal_entry_id' => $this->resource->getJournalEntryId(),
            'row_version' => $this->resource->getRowVersion(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
