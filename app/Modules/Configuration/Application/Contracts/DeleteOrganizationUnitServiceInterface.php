<?php
namespace Modules\Configuration\Application\Contracts;

interface DeleteOrganizationUnitServiceInterface
{
    public function execute(int $id): bool;
}
