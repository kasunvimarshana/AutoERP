<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
        $file = $data['file'];
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : null;
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $tenant = $this->tenantRepository->find($tenantId);
        if (! $tenant) {
            throw new TenantNotFoundException($tenantId);
        }

        $storedPath = null;

        try {
            $uuid = (string) Str::uuid();
            $storedPath = $this->storageStrategy->store($file, $tenantId);

            $attachment = new TenantAttachment(
                tenantId: $tenantId,
                uuid: $uuid,
                name: $file->getClientOriginalName(),
                filePath: $storedPath,
                mimeType: (string) $file->getMimeType(),
                size: (int) $file->getSize(),
                type: $type,
                metadata: $metadata,
            );

            return DB::transaction(function () use ($attachment, $type, $tenant, $storedPath): TenantAttachment {
                $saved = $this->attachmentRepository->save($attachment);

                if ($type === 'logo') {
                    $previousLogoPath = $tenant->getLogoPath();
                    $tenant->setLogoPath($storedPath);
                    $this->tenantRepository->save($tenant);

                    if ($previousLogoPath !== null && $previousLogoPath !== '' && $previousLogoPath !== $storedPath) {
                        DB::afterCommit(fn (): bool => $this->storageStrategy->delete($previousLogoPath));
                    }
                }

                return $saved;
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                $this->storageStrategy->delete($storedPath);
            }

            throw $exception;
        }
    }
}
