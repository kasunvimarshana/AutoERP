<?php

namespace Modules\Attachment\Application\Contracts;

use Modules\Attachment\Domain\Entities\Attachment;

interface DeleteAttachmentServiceInterface
{
    public function execute(Attachment $attachment): bool;
}
