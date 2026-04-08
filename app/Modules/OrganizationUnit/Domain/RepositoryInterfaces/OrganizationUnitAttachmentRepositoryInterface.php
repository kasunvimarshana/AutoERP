<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;

interface OrganizationUnitAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?OrganizationUnitAttachment;

    public function getByOrganizationUnit(int $orgUnitId, ?string $type = null): Collection;

    public function save(OrganizationUnitAttachment $attachment): OrganizationUnitAttachment;
}
