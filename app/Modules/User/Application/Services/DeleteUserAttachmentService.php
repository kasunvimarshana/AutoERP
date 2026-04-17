<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Domain\Exceptions\AttachmentNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;

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
        $attachmentId = (int) $data['attachment_id'];
        $attachment = $this->attachmentRepo->find($attachmentId);
        if (! $attachment) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        $deleted = $this->attachmentRepo->delete($attachmentId);
        if (! $deleted) {
            return false;
        }

        $fileDeleted = $this->storage->delete($attachment->getFilePath());
        if (! $fileDeleted) {
            throw new \RuntimeException('Failed to delete attachment file from storage.');
        }

        return true;
    }
}
