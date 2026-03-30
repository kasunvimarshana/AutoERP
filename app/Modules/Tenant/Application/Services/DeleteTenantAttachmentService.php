<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;

class DeleteTenantAttachmentService extends BaseService implements DeleteTenantAttachmentServiceInterface
{
    public function __construct(
        private readonly TenantAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($attachmentRepository);
    }

    protected function handle(array $data): bool
    {
        $attachmentId = $data['attachment_id'];
        $attachment   = $this->attachmentRepository->find($attachmentId);
        if (! $attachment) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        $this->storageStrategy->delete($attachment->getFilePath());

        return $this->attachmentRepository->delete($attachmentId);
    }
}
