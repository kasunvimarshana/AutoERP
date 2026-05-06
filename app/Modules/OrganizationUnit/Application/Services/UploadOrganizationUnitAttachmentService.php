<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
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
        private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository,
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository,
        private readonly FileStorageServiceInterface $storage,
    ) {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): OrganizationUnitAttachment
    {
        $organizationUnitId = (int) $data['org_unit_id'];
        /** @var array<string, mixed> $fileInfo */
        $fileInfo = is_array($data['file'] ?? null) ? $data['file'] : [];
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : null;
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $tmpPath = isset($fileInfo['tmp_path']) && is_string($fileInfo['tmp_path']) ? $fileInfo['tmp_path'] : null;
        $name = isset($fileInfo['name']) && is_string($fileInfo['name']) ? $fileInfo['name'] : null;
        $mimeType = isset($fileInfo['mime_type']) && is_string($fileInfo['mime_type']) ? $fileInfo['mime_type'] : null;
        $size = isset($fileInfo['size']) ? (int) $fileInfo['size'] : null;

        if (
            $tmpPath === null || $tmpPath === ''
            || $name === null || $name === ''
            || $mimeType === null || $mimeType === ''
            || $size === null || $size < 0
        ) {
            throw new \InvalidArgumentException('Invalid organization unit attachment payload.');
        }

        $organizationUnit = $this->organizationUnitRepository->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new OrganizationUnitNotFoundException($organizationUnitId);
        }

        $storedPath = null;

        try {
            $uuid = (string) Str::uuid();
            $storedPath = $this->storage->store($tmpPath, "organization-units/{$organizationUnitId}", $name);

            return DB::transaction(function () use ($organizationUnitId, $uuid, $name, $storedPath, $mimeType, $size, $type, $metadata, $organizationUnit): OrganizationUnitAttachment {
                $attachment = new OrganizationUnitAttachment(
                    tenantId: $organizationUnit->getTenantId(),
                    organizationUnitId: $organizationUnitId,
                    uuid: $uuid,
                    name: $name,
                    filePath: $storedPath,
                    mimeType: $mimeType,
                    size: $size,
                    type: $type,
                    metadata: $metadata,
                );

                return $this->attachmentRepository->save($attachment);
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                $this->storage->delete($storedPath);
            }

            throw $exception;
        }
    }
}
