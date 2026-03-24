<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UploadOrganizationUnitAttachmentService extends BaseService implements UploadOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $orgUnitRepository,
        protected OrganizationUnitAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($orgUnitRepository);
    }

    protected function handle(array $data): OrganizationUnitAttachment
    {
        $orgUnitId = $data['organization_unit_id'];
        $fileInfo = $data['file'];
        $type = $data['type'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $unit = $this->orgUnitRepository->find($orgUnitId);
        if (! $unit) {
            throw new OrganizationUnitNotFoundException($orgUnitId);
        }

        $tenantId = $unit->getTenantId();
        $uuid = (string) Str::uuid();
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

        return $this->attachmentRepo->save($attachment);
    }
}
