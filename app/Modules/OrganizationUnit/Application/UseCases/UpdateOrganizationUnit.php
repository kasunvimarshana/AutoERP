<?php

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;

class UpdateOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(int $id, OrganizationUnitData $data): OrganizationUnit
    {
        $unit = $this->orgUnitRepo->find($id);
        if (!$unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        $name = new Name($data->name);
        $code = $data->code !== null ? new Code($data->code) : null;
        $metadata = $data->metadata ? new Metadata($data->metadata) : null;

        $unit->updateDetails($name, $code, $data->description, $metadata);

        $saved = $this->orgUnitRepo->save($unit);
        event(new OrganizationUnitUpdated($saved));

        return $saved;
    }
}
