<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'reference_type' => $this->getReferenceType(),
            'reference_id' => $this->getReferenceId(),
            'tax_rate_id' => $this->getTaxRateId(),
            'taxable_amount' => $this->getTaxableAmount(),
            'tax_amount' => $this->getTaxAmount(),
            'tax_account_id' => $this->getTaxAccountId(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
