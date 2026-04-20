<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class OrganizationUnitAttachmentResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrganizationUnitId(),
            'uuid' => $this->getUuid(),
            'name' => $this->getName(),
            'file_path' => $this->getFilePath(),
            'url' => $this->storage->url($this->getFilePath()),
            'mime_type' => $this->getMimeType(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
