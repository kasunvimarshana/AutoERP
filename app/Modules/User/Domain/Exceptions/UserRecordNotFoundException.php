<?php

declare(strict_types=1);

namespace Modules\User\Domain\Exceptions;

use RuntimeException;

class UserRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self(sprintf('%s record [%s] was not found.', $resource, $id));
    }
}
