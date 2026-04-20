<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

interface DeleteOrganizationUnitTypeServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): mixed;
}
