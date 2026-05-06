<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

class OrganizationUnitNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Organization unit with ID {$id} was not found.");
    }
}
