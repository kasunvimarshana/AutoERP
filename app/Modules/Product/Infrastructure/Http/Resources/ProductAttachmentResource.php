<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'file_name' => $this->getFileName(),
            'file_path' => $this->getFilePath(),
            'file_type' => $this->getFileType(),
            'file_size' => $this->getFileSize(),
            'type' => $this->getType(),
            'is_primary' => $this->isPrimary(),
            'sort_order' => $this->getSortOrder(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
