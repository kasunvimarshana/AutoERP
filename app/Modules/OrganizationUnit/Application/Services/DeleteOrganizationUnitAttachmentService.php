<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\OrganizationUnit\Domain\Exceptions\AttachmentNotFoundException;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;

class DeleteOrganizationUnitAttachmentService extends BaseService implements DeleteOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        protected OrganizationUnitAttachmentRepositoryInterface $attachmentRepo,
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
