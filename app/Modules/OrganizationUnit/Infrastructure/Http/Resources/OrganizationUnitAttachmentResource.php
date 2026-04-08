<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;

class OrganizationUnitAttachmentResource extends JsonResource
{
    public function toArray($request)
    {
        // Resolve the URL via the pluggable storage strategy so that CDN,
        // signed-URL, or other alternative implementations are honoured
        // without touching this resource class.
        $strategy = app(AttachmentStorageStrategyInterface::class);

        return [
            'id'         => $this->getId(),
            'uuid'       => $this->getUuid(),
            'name'       => $this->getName(),
            'url'        => $strategy->url($this->getFilePath()),
            'mime_type'  => $this->getMimeType(),
            'size'       => $this->getSize(),
            'type'       => $this->getType(),
            'metadata'   => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
        ];
    }
}
