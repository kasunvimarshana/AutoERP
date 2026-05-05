<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class OrganizationUnitNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Organization unit with ID {$id} was not found.", 404);
    }
}
