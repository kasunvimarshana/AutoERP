<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exceptions;

use RuntimeException;

class CustomerRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
