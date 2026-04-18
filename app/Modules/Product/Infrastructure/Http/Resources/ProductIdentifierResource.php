<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductIdentifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'batch_id' => $this->getBatchId(),
            'serial_id' => $this->getSerialId(),
            'technology' => $this->getTechnology(),
            'format' => $this->getFormat(),
            'value' => $this->getValue(),
            'gs1_company_prefix' => $this->getGs1CompanyPrefix(),
            'gs1_application_identifiers' => $this->getGs1ApplicationIdentifiers(),
            'is_primary' => $this->isPrimary(),
            'is_active' => $this->isActive(),
            'format_config' => $this->getFormatConfig(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
