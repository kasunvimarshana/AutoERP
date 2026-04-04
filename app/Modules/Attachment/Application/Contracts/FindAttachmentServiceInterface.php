<?php

namespace Modules\Attachment\Application\Contracts;

use Modules\Attachment\Domain\Entities\Attachment;

interface FindAttachmentServiceInterface
{
    public function execute(int $id): ?Attachment;
}
