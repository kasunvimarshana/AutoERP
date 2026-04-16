<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UploadTenantAttachmentService extends BaseService implements UploadTenantAttachmentServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($tenantRepository);
    }

    /**
     * Expected $data keys:
     *   - tenant_id (int)
     *   - file      (UploadedFile)
     *   - type      (string|null)
     *   - metadata  (array|null)
     */
    protected function handle(array $data): TenantAttachment
    {
        $tenantId = (int) $data['tenant_id'];
        /** @var UploadedFile $file */
        $file     = $data['file'];
        $type     = $data['type'] ?? null;
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $tenant = $this->tenantRepository->find($tenantId);
        if (! $tenant) {
            throw new TenantNotFoundException($tenantId);
        }

        $uuid = (string) Str::uuid();
        $path = $this->storageStrategy->store($file, $tenantId);

        $attachment = new TenantAttachment(
            tenantId: $tenantId,
            uuid:     $uuid,
            name:     $file->getClientOriginalName(),
            filePath: $path,
            mimeType: (string) $file->getMimeType(),
            size:     (int) $file->getSize(),
            type:     $type,
            metadata: $metadata,
        );

        $saved = $this->attachmentRepository->save($attachment);

        // If this is a logo, update the tenant's logo_path
        if ($type === 'logo') {
            $tenant->setLogoPath($path);
            $this->tenantRepository->save($tenant);
        }

        return $saved;
    }
}
