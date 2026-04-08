<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Gs1BarcodeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->getId(),
            'tenant_id'               => $this->getTenantId(),
            'gs1_identifier_id'       => $this->getGs1IdentifierId(),
            'barcode_type'            => $this->getBarcodeType(),
            'barcode_data'            => $this->getBarcodeData(),
            'application_identifiers' => $this->getApplicationIdentifiers(),
            'is_primary'              => $this->isPrimary(),
            'is_active'               => $this->isActive(),
            'metadata'                => $this->getMetadata()->toArray(),
            'created_at'              => $this->getCreatedAt()->format('c'),
            'updated_at'              => $this->getUpdatedAt()->format('c'),
        ];
    }
}
