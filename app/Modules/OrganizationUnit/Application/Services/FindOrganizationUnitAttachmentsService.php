<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

class FindOrganizationUnitAttachmentsService implements FindOrganizationUnitAttachmentsServiceInterface
{
    public function __construct(private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository) {}

    public function find(int $id): ?OrganizationUnitAttachment
    {
        return $this->attachmentRepository->find($id);
    }

    public function findByUuid(string $uuid): ?OrganizationUnitAttachment
    {
        return $this->attachmentRepository->findByUuid($uuid);
    }

    public function getByOrganizationUnit(int $organizationUnitId, ?string $type = null): Collection
    {
        return $this->attachmentRepository->getByOrganizationUnit($organizationUnitId, $type);
    }

    public function paginateByOrganizationUnit(int $organizationUnitId, ?string $type, int $perPage, int $page): LengthAwarePaginator
    {
        $repository = $this->attachmentRepository
            ->resetCriteria()
            ->where('org_unit_id', $organizationUnitId);

        if ($type !== null && $type !== '') {
            $repository->where('type', $type);
        }

        return $repository
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
