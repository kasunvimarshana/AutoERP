<?php

namespace Modules\Attachment\Application\Services;

use Modules\Attachment\Application\Contracts\FindAttachmentServiceInterface;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class FindAttachmentService implements FindAttachmentServiceInterface
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repo,
    ) {}

    public function execute(int $id): ?Attachment
    {
        return $this->repo->findById($id);
    }
}
