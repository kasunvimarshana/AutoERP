<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UploadOrganizationUnitAttachmentService extends BaseService implements UploadOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $orgUnitRepository,
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($orgUnitRepository);
    }

    /**
     * Expected $data keys:
     *   - organization_unit_id (int)
     *   - file                 (UploadedFile)
     *   - type                 (string|null)
     *   - metadata             (array|null)
     */
    protected function handle(array $data): OrganizationUnitAttachment
    {
        $orgUnitId = (int) $data['organization_unit_id'];
        /** @var UploadedFile $file */
        $file = $data['file'];

        $unit = $this->orgUnitRepository->find($orgUnitId);
        if (! $unit) {
            throw new OrganizationUnitNotFoundException($orgUnitId);
        }

        $path = $this->storageStrategy->store($file, $orgUnitId);

        $dto = OrganizationUnitAttachmentData::fromArray([
            'organization_unit_id' => $orgUnitId,
            'tenant_id'            => $unit->getTenantId(),
            'name'                 => $file->getClientOriginalName(),
            'file_path'            => $path,
            'mime_type'            => (string) $file->getMimeType(),
            'size'                 => (int) $file->getSize(),
            'type'                 => $data['type'] ?? null,
            'metadata'             => isset($data['metadata']) && is_array($data['metadata'])
                                        ? $data['metadata']
                                        : null,
        ]);

        $attachment = new OrganizationUnitAttachment(
            tenantId:           $dto->tenant_id,
            organizationUnitId: $dto->organization_unit_id,
            uuid:               (string) Str::uuid(),
            name:               $dto->name,
            filePath:           $dto->file_path,
            mimeType:           $dto->mime_type,
            size:               $dto->size,
            type:               $dto->type,
            metadata:           $dto->metadata,
        );

        return $this->attachmentRepository->save($attachment);
    }
}
