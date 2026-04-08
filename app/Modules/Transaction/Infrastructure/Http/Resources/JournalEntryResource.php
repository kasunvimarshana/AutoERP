<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'transaction_id' => $this->getTransactionId(),
            'account_code'   => $this->getAccountCode(),
            'account_name'   => $this->getAccountName(),
            'debit_amount'   => $this->getDebitAmount(),
            'credit_amount'  => $this->getCreditAmount(),
            'net_amount'     => $this->getNetAmount(),
            'is_debit'       => $this->isDebit(),
            'description'    => $this->getDescription(),
            'status'         => $this->getStatus(),
            'posted_at'      => $this->getPostedAt()?->format('c'),
            'metadata'       => $this->getMetadata()->toArray(),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
