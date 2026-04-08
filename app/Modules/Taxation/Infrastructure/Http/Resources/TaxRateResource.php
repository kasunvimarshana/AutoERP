<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->getId(),
            'tenant_id'          => $this->getTenantId(),
            'name'               => $this->getName(),
            'code'               => $this->getCode(),
            'tax_type'           => $this->getTaxType(),
            'calculation_method' => $this->getCalculationMethod(),
            'rate'               => $this->getRate(),
            'jurisdiction'       => $this->getJurisdiction(),
            'is_active'          => $this->isActive(),
            'description'        => $this->getDescription(),
            'effective_from'     => $this->getEffectiveFrom()?->format('Y-m-d'),
            'effective_to'       => $this->getEffectiveTo()?->format('Y-m-d'),
            'metadata'           => $this->getMetadata()->toArray(),
            'created_at'         => $this->getCreatedAt()->format('c'),
            'updated_at'         => $this->getUpdatedAt()->format('c'),
        ];
    }
}
