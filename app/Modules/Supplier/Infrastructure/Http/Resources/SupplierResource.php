<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'user_id' => $this->getUserId(),
            'supplier_code' => $this->getSupplierCode(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'org_unit_id' => $this->getOrgUnitId(),
            'tax_number' => $this->getTaxNumber(),
            'registration_number' => $this->getRegistrationNumber(),
            'currency_id' => $this->getCurrencyId(),
            'payment_terms_days' => $this->getPaymentTermsDays(),
            'ap_account_id' => $this->getApAccountId(),
            'status' => $this->getStatus(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
