<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetOwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'name' => $this->resource->getName(),
            'type' => $this->resource->getType(),
            'contact_person' => $this->resource->getContactPerson(),
            'email' => $this->resource->getEmail(),
            'phone' => $this->resource->getPhone(),
            'notes' => $this->resource->getNotes(),
        ];
    }
}
