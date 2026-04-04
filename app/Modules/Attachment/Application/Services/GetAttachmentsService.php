<?php

namespace Modules\Attachment\Application\Services;

use Modules\Attachment\Application\Contracts\GetAttachmentsServiceInterface;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class GetAttachmentsService implements GetAttachmentsServiceInterface
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repo,
    ) {}

    public function execute(string $attachableType, int $attachableId): array
    {
        return $this->repo->findByAttachable($attachableType, $attachableId);
    }
}
