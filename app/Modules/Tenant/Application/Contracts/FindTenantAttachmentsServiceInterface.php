<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Tenant\Domain\Entities\TenantAttachment;

/**
 * Contract for querying tenant attachments.
 *
 * Separates attachment read operations from write concerns, adhering to the
 * Interface Segregation and Single Responsibility principles. Controllers must
 * depend on this interface rather than on TenantAttachmentRepositoryInterface
 * directly.
 */
interface FindTenantAttachmentsServiceInterface
{
    /**
     * Return all attachments belonging to a tenant, optionally filtered by type.
     *
     * @return Collection<int, TenantAttachment>
     */
    public function findByTenant(int $tenantId, ?string $type = null): Collection;

    /**
     * Find a single attachment by its UUID.
     */
    public function findByUuid(string $uuid): ?TenantAttachment;

    /**
     * Find a single attachment by its ID.
     */
    public function find(int $id): ?TenantAttachment;
}
