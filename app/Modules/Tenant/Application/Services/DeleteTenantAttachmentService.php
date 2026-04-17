<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class DeleteTenantAttachmentService extends BaseService implements DeleteTenantAttachmentServiceInterface
{
    public function __construct(
        private readonly TenantAttachmentRepositoryInterface $attachmentRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly AttachmentStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($attachmentRepository);
    }

    protected function handle(array $data): bool
    {
        $attachmentId = (int) $data['attachment_id'];
        $attachment   = $this->attachmentRepository->find($attachmentId);
        if (! $attachment) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        $filePath = $attachment->getFilePath();
        $attachmentType = $attachment->getType();
        $tenantId = $attachment->getTenantId();

        $deleted = $this->attachmentRepository->delete($attachmentId);
        if (! $deleted) {
            return false;
        }

        if ($attachmentType === 'logo') {
            $tenant = $this->tenantRepository->find($tenantId);
            if ($tenant && $tenant->getLogoPath() === $filePath) {
                $tenant->setLogoPath(null);
                $this->tenantRepository->save($tenant);
            }
        }

        $fileDeleted = $this->storageStrategy->delete($filePath);
        if (! $fileDeleted) {
            throw new \RuntimeException('Failed to delete attachment file from storage.');
        }

        return true;
    }
}
