<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReturnAuthorizationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'rma_number'     => $this->getRmaNumber(),
            'return_type'    => $this->getReturnType(),
            'party_id'       => $this->getPartyId(),
            'party_type'     => $this->getPartyType(),
            'reason'         => $this->getReason(),
            'status'         => $this->getStatus(),
            'authorized_by'  => $this->getAuthorizedBy(),
            'authorized_at'  => $this->getAuthorizedAt()?->format('c'),
            'expires_at'     => $this->getExpiresAt()?->format('c'),
            'cancelled_at'   => $this->getCancelledAt()?->format('c'),
            'stock_return_id'=> $this->getStockReturnId(),
            'notes'          => $this->getNotes(),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
