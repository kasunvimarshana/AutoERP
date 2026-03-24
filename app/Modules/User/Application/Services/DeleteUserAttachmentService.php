<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Domain\Exceptions\AttachmentNotFoundException;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;

class DeleteUserAttachmentService extends BaseService implements DeleteUserAttachmentServiceInterface
{
    public function __construct(
        protected UserAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($attachmentRepo);
    }

    protected function handle(array $data): bool
    {
        $attachmentId = $data['attachment_id'];
        $attachment = $this->attachmentRepo->find($attachmentId);
        if (!$attachment) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        $this->storage->delete($attachment->getFilePath());
        return $this->attachmentRepo->delete($attachmentId);
    }
}
