<?php
namespace Modules\Configuration\Application\Contracts;
use Modules\Configuration\Application\DTOs\OrganizationUnitData;
use Modules\Configuration\Domain\Entities\OrganizationUnit;

interface CreateOrganizationUnitServiceInterface
{
    public function execute(OrganizationUnitData $data): OrganizationUnit;
}
