<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenant\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class BulkUploadTenantAttachmentsService implements BulkUploadTenantAttachmentsServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantAttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {}

    /**
     * Upload multiple attachments inside a single transaction.
     *
     * Expected $data keys:
     *   - tenant_id (int)
     *   - files     (UploadedFile[])
     *   - type      (string|null)
     *   - metadata  (array|null)
     *
     * @return Collection<int, TenantAttachment>
     */
    public function execute(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $tenantId = (int) $data['tenant_id'];
            $files    = $data['files'] ?? [];
            $type     = $data['type'] ?? null;
            $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

            $tenant = $this->tenantRepository->find($tenantId);
            if (! $tenant) {
                throw new TenantNotFoundException($tenantId);
            }

            $saved = new Collection;

            foreach ($files as $file) {
                /** @var UploadedFile $file */
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

                $saved->push($this->attachmentRepository->save($attachment));
            }

            return $saved;
        });
    }
}
