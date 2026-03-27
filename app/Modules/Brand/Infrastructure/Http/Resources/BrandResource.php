<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray($request): array
    {
        $logo = $this->getLogo();

        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName(),
            'slug'        => $this->getSlug(),
            'description' => $this->getDescription(),
            'website'     => $this->getWebsite(),
            'status'      => $this->getStatus(),
            'attributes'  => $this->getAttributes(),
            'metadata'    => $this->getMetadata(),
            'logo'        => $logo ? new BrandLogoResource($logo) : null,
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
