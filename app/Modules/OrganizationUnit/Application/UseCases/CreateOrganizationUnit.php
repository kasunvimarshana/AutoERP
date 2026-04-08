<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class CreateOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(OrganizationUnitData $data): OrganizationUnit
    {
        $name = new Name($data->name);
        $code = $data->code !== null ? new Code($data->code) : null;
        $metadata = $data->metadata ? new Metadata($data->metadata) : null;

        $unit = new OrganizationUnit(
            tenantId: $data->tenant_id,
            name: $name,
            code: $code,
            description: $data->description,
            metadata: $metadata,
            parentId: $data->parent_id
        );

        $saved = $this->orgUnitRepo->save($unit);

        event(new OrganizationUnitCreated($saved));

        return $saved;
    }
}
