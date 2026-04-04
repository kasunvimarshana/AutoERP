<?php
namespace Modules\Configuration\Application\Contracts;
use Modules\Configuration\Application\DTOs\OrganizationUnitData;
use Modules\Configuration\Domain\Entities\OrganizationUnit;

interface UpdateOrganizationUnitServiceInterface
{
    public function execute(int $id, OrganizationUnitData $data): OrganizationUnit;
}
