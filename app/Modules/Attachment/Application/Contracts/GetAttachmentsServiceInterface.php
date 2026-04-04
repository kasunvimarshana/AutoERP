<?php

namespace Modules\Attachment\Application\Contracts;

interface GetAttachmentsServiceInterface
{
    public function execute(string $attachableType, int $attachableId): array;
}
