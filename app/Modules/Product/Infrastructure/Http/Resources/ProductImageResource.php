<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->getId(),
            'uuid'       => $this->getUuid(),
            'product_id' => $this->getProductId(),
            'name'       => $this->getName(),
            'file_path'  => $this->getFilePath(),
            'mime_type'  => $this->getMimeType(),
            'size'       => $this->getSize(),
            'sort_order' => $this->getSortOrder(),
            'is_primary' => $this->isPrimary(),
            'metadata'   => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
