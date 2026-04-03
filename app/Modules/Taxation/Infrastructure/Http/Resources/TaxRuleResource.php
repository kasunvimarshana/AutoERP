<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName(),
            'tax_rate_id' => $this->getTaxRateId(),
            'entity_type' => $this->getEntityType(),
            'entity_id'   => $this->getEntityId(),
            'jurisdiction'=> $this->getJurisdiction(),
            'priority'    => $this->getPriority(),
            'is_active'   => $this->isActive(),
            'description' => $this->getDescription(),
            'metadata'    => $this->getMetadata()->toArray(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
