<?php

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Tenant\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;

class DeleteTenantAttachmentService extends BaseService implements DeleteTenantAttachmentServiceInterface
{
    public function __construct(
        protected TenantAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($attachmentRepo); // use attachment repository as main
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
