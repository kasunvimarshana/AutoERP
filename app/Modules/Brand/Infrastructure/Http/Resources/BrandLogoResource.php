<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandLogoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->getId(),
            'uuid'       => $this->getUuid(),
            'brand_id'   => $this->getBrandId(),
            'name'       => $this->getName(),
            'file_path'  => $this->getFilePath(),
            'mime_type'  => $this->getMimeType(),
            'size'       => $this->getSize(),
            'metadata'   => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
