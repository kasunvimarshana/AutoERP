<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditMemoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'reference_number' => $this->getReferenceNumber(),
            'stock_return_id'  => $this->getStockReturnId(),
            'party_id'         => $this->getPartyId(),
            'party_type'       => $this->getPartyType(),
            'status'           => $this->getStatus(),
            'amount'           => $this->getAmount(),
            'currency'         => $this->getCurrency(),
            'issue_date'       => $this->getIssueDate()?->format('c'),
            'applied_date'     => $this->getAppliedDate()?->format('c'),
            'voided_date'      => $this->getVoidedDate()?->format('c'),
            'notes'            => $this->getNotes(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}
