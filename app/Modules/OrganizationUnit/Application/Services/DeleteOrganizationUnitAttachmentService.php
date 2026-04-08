<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Exceptions\AttachmentNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

class DeleteOrganizationUnitAttachmentService extends BaseService implements DeleteOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
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
