<?php

declare(strict_types=1);

namespace Modules\Attachment\Application\Contracts;

interface DeleteAttachmentServiceInterface
{
    public function execute(int $id): bool;
}
