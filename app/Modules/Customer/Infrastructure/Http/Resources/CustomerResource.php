<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'user_id' => $this->getUserId(),
            'customer_code' => $this->getCustomerCode(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'org_unit_id' => $this->getOrgUnitId(),
            'tax_number' => $this->getTaxNumber(),
            'registration_number' => $this->getRegistrationNumber(),
            'currency_id' => $this->getCurrencyId(),
            'credit_limit' => $this->getCreditLimit(),
            'payment_terms_days' => $this->getPaymentTermsDays(),
            'ar_account_id' => $this->getArAccountId(),
            'status' => $this->getStatus(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
