<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Exceptions;

use RuntimeException;

class SystemUserRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self(sprintf('%s record [%s] was not found.', $resource, $id));
    }
}
