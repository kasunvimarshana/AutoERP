<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'reference_number' => $this->getReferenceNumber(),
            'transaction_type' => $this->getTransactionType(),
            'status'           => $this->getStatus(),
            'amount'           => $this->getAmount(),
            'currency_code'    => $this->getCurrencyCode(),
            'exchange_rate'    => $this->getExchangeRate(),
            'transaction_date' => $this->getTransactionDate()->format('Y-m-d'),
            'description'      => $this->getDescription(),
            'reference_type'   => $this->getReferenceType(),
            'reference_id'     => $this->getReferenceId(),
            'posted_at'        => $this->getPostedAt()?->format('c'),
            'voided_at'        => $this->getVoidedAt()?->format('c'),
            'void_reason'      => $this->getVoidReason(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}
