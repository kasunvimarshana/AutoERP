<?php

namespace Modules\Attachment\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Attachment\Domain\Entities\Attachment;

class AttachmentResource extends JsonResource
{
    public function __construct(private readonly Attachment $attachment)
    {
        parent::__construct($attachment);
    }

    public function toArray($request): array
    {
        return [
            'id'              => $this->attachment->id,
            'tenant_id'       => $this->attachment->tenantId,
            'attachable_type' => $this->attachment->attachableType,
            'attachable_id'   => $this->attachment->attachableId,
            'disk'            => $this->attachment->disk,
            'path'            => $this->attachment->path,
            'original_name'   => $this->attachment->originalName,
            'mime_type'       => $this->attachment->mimeType,
            'size'            => $this->attachment->size,
            'label'           => $this->attachment->label,
            'uploaded_by'     => $this->attachment->uploadedBy,
        ];
    }
}
