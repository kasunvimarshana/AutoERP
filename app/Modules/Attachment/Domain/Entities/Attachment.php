<?php

namespace Modules\Attachment\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Attachment extends BaseEntity
{
    public function __construct(
        ?int $id,
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
    ) {
        parent::__construct($id);
    }
}
