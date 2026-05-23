<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Exceptions;

use RuntimeException;

class VoucherRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
