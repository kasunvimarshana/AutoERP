<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\BulkUploadOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class BulkUploadOrganizationUnitAttachmentsService implements BulkUploadOrganizationUnitAttachmentsServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $orgUnitRepository,
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {}

    /**
     * Upload multiple attachments inside a single transaction.
     *
     * Expected $data keys:
     *   - organization_unit_id (int)
     *   - files                (UploadedFile[])
     *   - type                 (string|null)
     *   - metadata             (array|null)
     *
     * @return Collection<int, OrganizationUnitAttachment>
     */
    public function execute(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $orgUnitId = (int) $data['organization_unit_id'];
            $files     = $data['files'] ?? [];
            $type      = $data['type'] ?? null;
            $metadata  = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

            $unit = $this->orgUnitRepository->find($orgUnitId);
            if (! $unit) {
                throw new OrganizationUnitNotFoundException($orgUnitId);
            }

            $tenantId = $unit->getTenantId();
            $saved    = new Collection;

            foreach ($files as $file) {
                /** @var UploadedFile $file */
                $path = $this->storageStrategy->store($file, $orgUnitId);

                $dto = OrganizationUnitAttachmentData::fromArray([
                    'organization_unit_id' => $orgUnitId,
                    'tenant_id'            => $tenantId,
                    'name'                 => $file->getClientOriginalName(),
                    'file_path'            => $path,
                    'mime_type'            => (string) $file->getMimeType(),
                    'size'                 => (int) $file->getSize(),
                    'type'                 => $type,
                    'metadata'             => $metadata,
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

                $saved->push($this->attachmentRepository->save($attachment));
            }

            return $saved;
        });
    }
}
