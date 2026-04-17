<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

class OrganizationUnitAttachmentNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Organization unit attachment with ID {$id} was not found.");
    }
}
