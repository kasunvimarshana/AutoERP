<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Http\UploadedFile;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\ReplaceOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\AttachmentNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

/**
 * Replaces the stored file of an existing attachment.
 *
 * The old file is deleted from storage before the new file is persisted.
 * All mutation occurs within the transaction managed by BaseService::execute().
 *
 * Expected $data keys:
 *   - attachment_id (int)
 *   - file          (UploadedFile)
 *   - type          (string|null)   — if provided, replaces the existing type
 *   - metadata      (array|null)    — if provided, replaces the existing metadata
 */
class ReplaceOrganizationUnitAttachmentService extends BaseService implements ReplaceOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($attachmentRepository);
    }

    protected function handle(array $data): OrganizationUnitAttachment
    {
        $attachmentId = (int) $data['attachment_id'];
        /** @var UploadedFile $file */
        $file = $data['file'];

        $existing = $this->attachmentRepository->find($attachmentId);
        if (! $existing) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        // Remove old file from storage before writing the replacement.
        $this->storageStrategy->delete($existing->getFilePath());

        $newPath = $this->storageStrategy->store($file, $existing->getOrganizationUnitId());

        $dto = OrganizationUnitAttachmentData::fromArray([
            'organization_unit_id' => $existing->getOrganizationUnitId(),
            'tenant_id'            => $existing->getTenantId(),
            'name'                 => $file->getClientOriginalName(),
            'file_path'            => $newPath,
            'mime_type'            => (string) $file->getMimeType(),
            'size'                 => (int) $file->getSize(),
            'type'                 => array_key_exists('type', $data) ? $data['type'] : $existing->getType(),
            'metadata'             => array_key_exists('metadata', $data) ? $data['metadata'] : $existing->getMetadata(),
        ]);

        $replacement = new OrganizationUnitAttachment(
            tenantId:           $dto->tenant_id,
            organizationUnitId: $dto->organization_unit_id,
            uuid:               $existing->getUuid(),   // keep the same UUID
            name:               $dto->name,
            filePath:           $dto->file_path,
            mimeType:           $dto->mime_type,
            size:               $dto->size,
            type:               $dto->type,
            metadata:           $dto->metadata,
            id:                 $existing->getId(),
        );

        return $this->attachmentRepository->save($replacement);
    }
}
