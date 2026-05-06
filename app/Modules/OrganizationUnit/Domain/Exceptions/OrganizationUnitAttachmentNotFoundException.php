<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class OrganizationUnitAttachmentNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Organization unit attachment with ID {$id} was not found.", 404);
    }
}
