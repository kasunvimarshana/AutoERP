<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'user_id'        => $this->getUserId(),
            'name'           => $this->getName(),
            'code'           => $this->getCode(),
            'email'          => $this->getEmail(),
            'phone'          => $this->getPhone(),
            'address'        => $this->getAddress(),
            'contact_person' => $this->getContactPerson(),
            'payment_terms'  => $this->getPaymentTerms(),
            'currency'       => $this->getCurrency(),
            'tax_number'     => $this->getTaxNumber(),
            'status'         => $this->getStatus(),
            'type'           => $this->getType(),
            'attributes'     => $this->getAttributes(),
            'metadata'       => $this->getMetadata(),
            'has_user_access'=> $this->hasUserAccess(),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
