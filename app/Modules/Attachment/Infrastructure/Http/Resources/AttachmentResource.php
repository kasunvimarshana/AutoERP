<?php

declare(strict_types=1);

namespace Modules\Attachment\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Attachment\Domain\Entities\Attachment;

class AttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Attachment $a */
        $a = $this->resource;
        return [
            'id'              => $a->getId(),
            'tenant_id'       => $a->getTenantId(),
            'attachable_type' => $a->getAttachableType(),
            'attachable_id'   => $a->getAttachableId(),
            'file_name'       => $a->getFileName(),
            'file_path'       => $a->getFilePath(),
            'mime_type'       => $a->getMimeType(),
            'file_size'       => $a->getFileSize(),
            'description'     => $a->getDescription(),
            'category'        => $a->getCategory(),
            'created_at'      => $a->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'      => $a->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
