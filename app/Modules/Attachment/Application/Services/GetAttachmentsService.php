<?php

declare(strict_types=1);

namespace Modules\Attachment\Application\Services;

use Illuminate\Support\Collection;
use Modules\Attachment\Application\Contracts\GetAttachmentsServiceInterface;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class GetAttachmentsService implements GetAttachmentsServiceInterface
{
    public function __construct(private readonly AttachmentRepositoryInterface $repo) {}

    public function findById(int $id): Attachment
    {
        $attachment = $this->repo->findById($id);
        if (!$attachment) {
            throw new AttachmentNotFoundException($id);
        }
        return $attachment;
    }

    public function findByAttachable(string $type, int $id): Collection
    {
        return $this->repo->findByAttachable($type, $id);
    }
}
