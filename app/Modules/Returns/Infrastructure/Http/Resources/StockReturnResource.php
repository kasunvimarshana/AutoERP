<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->getId(),
            'tenant_id'              => $this->getTenantId(),
            'reference_number'       => $this->getReferenceNumber(),
            'return_type'            => $this->getReturnType(),
            'status'                 => $this->getStatus(),
            'party_id'               => $this->getPartyId(),
            'party_type'             => $this->getPartyType(),
            'original_reference_id'  => $this->getOriginalReferenceId(),
            'original_reference_type'=> $this->getOriginalReferenceType(),
            'return_reason'          => $this->getReturnReason(),
            'total_amount'           => $this->getTotalAmount(),
            'currency'               => $this->getCurrency(),
            'restock'                => $this->getRestock(),
            'restock_location_id'    => $this->getRestockLocationId(),
            'restocking_fee'         => $this->getRestockingFee(),
            'credit_memo_issued'     => $this->getCreditMemoIssued(),
            'credit_memo_reference'  => $this->getCreditMemoReference(),
            'approved_by'            => $this->getApprovedBy(),
            'approved_at'            => $this->getApprovedAt()?->format('c'),
            'processed_by'           => $this->getProcessedBy(),
            'processed_at'           => $this->getProcessedAt()?->format('c'),
            'notes'                  => $this->getNotes(),
            'metadata'               => $this->getMetadata()->toArray(),
            'created_at'             => $this->getCreatedAt()->format('c'),
            'updated_at'             => $this->getUpdatedAt()->format('c'),
        ];
    }
}
