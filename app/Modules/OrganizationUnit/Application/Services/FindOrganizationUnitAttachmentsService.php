<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

/**
 * Dedicated read service for organization-unit attachments.
 *
 * Wraps the attachment repository so that higher-level layers (controllers,
 * use-cases) depend only on this service interface rather than on the
 * repository abstraction, keeping the dependency graph clean.
 */
class FindOrganizationUnitAttachmentsService implements FindOrganizationUnitAttachmentsServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository
    ) {}

    /**
     * @return Collection<int, OrganizationUnitAttachment>
     */
    public function findByOrganizationUnit(int $orgUnitId, ?string $type = null): Collection
    {
        return $this->attachmentRepository->getByOrganizationUnit($orgUnitId, $type);
    }

    public function findByUuid(string $uuid): ?OrganizationUnitAttachment
    {
        return $this->attachmentRepository->findByUuid($uuid);
    }

    public function find(int $id): ?OrganizationUnitAttachment
    {
        return $this->attachmentRepository->find($id);
    }
}
