<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Gs1IdentifierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'identifier_type'  => $this->getIdentifierType(),
            'identifier_value' => $this->getIdentifierValue(),
            'entity_type'      => $this->getEntityType(),
            'entity_id'        => $this->getEntityId(),
            'is_active'        => $this->isActive(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}
