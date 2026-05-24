<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Exceptions;

use RuntimeException;

class PricingRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
