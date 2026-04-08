<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;

/**
 * Contract for querying organization-unit attachments.
 *
 * Separates attachment read operations from write concerns, adhering to the
 * Interface Segregation and Single Responsibility principles. Controllers must
 * depend on this interface rather than on OrganizationUnitAttachmentRepositoryInterface
 * directly.
 */
interface FindOrganizationUnitAttachmentsServiceInterface
{
    /**
     * Return all attachments belonging to an organization unit, optionally filtered by type.
     *
     * @return Collection<int, OrganizationUnitAttachment>
     */
    public function findByOrganizationUnit(int $orgUnitId, ?string $type = null): Collection;

    /**
     * Find a single attachment by its UUID.
     */
    public function findByUuid(string $uuid): ?OrganizationUnitAttachment;

    /**
     * Find a single attachment by its ID.
     */
    public function find(int $id): ?OrganizationUnitAttachment;
}
