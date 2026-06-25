<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Contracts;

interface OrganizationUnitPopulationReaderInterface
{
    public function activeCount(): int;
}
