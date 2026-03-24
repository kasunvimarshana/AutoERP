<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UploadTenantAttachmentService extends BaseService implements UploadTenantAttachmentServiceInterface
{
    private TenantRepositoryInterface $tenantRepository;

    public function __construct(
        TenantRepositoryInterface $repository,
        protected TenantAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($repository);
        $this->tenantRepository = $repository;
    }

    protected function handle(array $data): TenantAttachment
    {
        $tenantId = $data['tenant_id'];
        $fileInfo = $data['file'];
        $type = $data['type'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $tenant = $this->tenantRepository->find($tenantId);
        if (! $tenant) {
            throw new TenantNotFoundException($tenantId);
        }

        $uuid = (string) Str::uuid();
        $path = $this->storage->store($fileInfo['tmp_path'], "tenants/{$tenantId}", $fileInfo['name']);

        $attachment = new TenantAttachment(
            tenantId: $tenantId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            type: $type,
            metadata: $metadata
        );

        $saved = $this->attachmentRepo->save($attachment);

        // If this is a logo, update tenant's logo_path
        if ($type === 'logo') {
            $tenant->setLogoPath($path);
            $this->tenantRepository->save($tenant);
        }

        return $saved;
    }
}
