<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Collection;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;

/**
 * Dedicated read service for tenant attachments.
 *
 * Wraps the attachment repository so that higher-level layers (controllers,
 * use-cases) depend only on this service interface rather than on the
 * repository abstraction, keeping the dependency graph clean.
 */
class FindTenantAttachmentsService implements FindTenantAttachmentsServiceInterface
{
    public function __construct(
        private readonly TenantAttachmentRepositoryInterface $attachmentRepository
    ) {}

    /**
     * @return Collection<int, TenantAttachment>
     */
    public function findByTenant(int $tenantId, ?string $type = null): Collection
    {
        return $this->attachmentRepository->getByTenant($tenantId, $type);
    }

    public function findByUuid(string $uuid): ?TenantAttachment
    {
        return $this->attachmentRepository->findByUuid($uuid);
    }

    public function find(int $id): ?TenantAttachment
    {
        return $this->attachmentRepository->find($id);
    }
}
