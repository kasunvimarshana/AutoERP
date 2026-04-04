<?php

namespace Modules\Attachment\Application\Contracts;

use Modules\Attachment\Application\DTOs\UploadAttachmentData;
use Modules\Attachment\Domain\Entities\Attachment;

interface UploadAttachmentServiceInterface
{
    public function execute(UploadAttachmentData $data): Attachment;
}
