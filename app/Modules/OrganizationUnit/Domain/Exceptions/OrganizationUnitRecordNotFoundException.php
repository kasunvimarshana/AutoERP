<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

use RuntimeException;

class OrganizationUnitRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self(sprintf('%s record [%s] was not found.', $resource, $id));
    }
}
