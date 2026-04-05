<?php

declare(strict_types=1);

namespace Modules\Attachment\Application\Services;

use Modules\Attachment\Application\Contracts\DeleteAttachmentServiceInterface;
use Modules\Attachment\Domain\Events\AttachmentDeleted;
use Modules\Attachment\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class DeleteAttachmentService implements DeleteAttachmentServiceInterface
{
    public function __construct(private readonly AttachmentRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $attachment = $this->repo->findById($id);
        if (!$attachment) {
            throw new AttachmentNotFoundException($id);
        }

        $result = $this->repo->delete($id);

        if ($result) {
            event(new AttachmentDeleted(
                $attachment->getTenantId(),
                $attachment->getId(),
                $attachment->getAttachableType(),
                $attachment->getAttachableId()
            ));
        }

        return $result;
    }
}
