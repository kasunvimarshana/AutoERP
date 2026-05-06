<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitAttachmentNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

class DeleteOrganizationUnitAttachmentService extends BaseService implements DeleteOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($attachmentRepository);
    }

    protected function handle(array $data): bool
    {
        $attachmentId = (int) $data['attachment_id'];

        return DB::transaction(function () use ($attachmentId): bool {
            $attachment = $this->attachmentRepository->find($attachmentId);
            if (! $attachment) {
                throw new OrganizationUnitAttachmentNotFoundException($attachmentId);
            }

            $filePath = $attachment->getFilePath();

            $deleted = $this->attachmentRepository->delete($attachmentId);
            if (! $deleted) {
                return false;
            }

            $fileDeleted = $this->storage->delete($filePath);
            if (! $fileDeleted) {
                throw new \RuntimeException('Failed to delete organization unit attachment file from storage.');
            }

            return true;
        });
    }
}
