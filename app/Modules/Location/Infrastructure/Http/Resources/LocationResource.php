<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName()->value(),
            'type'        => $this->getType(),
            'code'        => $this->getCode()?->value(),
            'description' => $this->getDescription(),
            'latitude'    => $this->getLatitude(),
            'longitude'   => $this->getLongitude(),
            'timezone'    => $this->getTimezone(),
            'metadata'    => $this->getMetadata()->toArray(),
            'parent_id'   => $this->getParentId(),
            'children'    => LocationResource::collection($this->getChildren()),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
