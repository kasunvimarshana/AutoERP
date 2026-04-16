<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class TenantAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        $storage = app(FileStorageServiceInterface::class);

        return [
            'id' => $this->getId(),
            'uuid' => $this->getUuid(),
            'name' => $this->getName(),
            'url' => $storage->url($this->getFilePath()),
            'mime_type' => $this->getMimeType(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
        ];
    }
}
