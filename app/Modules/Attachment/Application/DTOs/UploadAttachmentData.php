<?php

namespace Modules\Attachment\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class UploadAttachmentData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $attachableType,
        public readonly int $attachableId,
        public readonly string $disk,
        public readonly string $path,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $label = null,
        public readonly ?int $uploadedBy = null,
    ) {}
}
