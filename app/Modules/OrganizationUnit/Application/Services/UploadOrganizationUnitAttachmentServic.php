<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\Core\Application\Services\FileStorageServiceInterface;
use Ramsey\Uuid\Uuid;

class UploadOrganizationUnitAttachmentService extends BaseService
{
    public function __construct(
        OrganizationUnitRepositoryInterface $repository,
        protected OrganizationUnitAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($repository);
    }

    protected function handle(array $data): OrganizationUnitAttachment
    {
        $orgUnitId = $data['organization_unit_id'];
        $fileInfo = $data['file'];
        $type = $data['type'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $unit = $this->repository->find($orgUnitId);
        if (!$unit) {
            throw new \RuntimeException('Organization unit not found');
        }

        $tenantId = $unit->getTenantId();
        $uuid = Uuid::uuid4()->toString();
        $path = $this->storage->store($fileInfo['tmp_path'], "org-units/{$orgUnitId}", $fileInfo['name']);

        $attachment = new OrganizationUnitAttachment(
            tenantId: $tenantId,
            organizationUnitId: $orgUnitId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            type: $type,
            metadata: $metadata
        );

        $saved = $this->attachmentRepo->save($attachment);
        // Optionally fire event
        return $saved;
    }
}
