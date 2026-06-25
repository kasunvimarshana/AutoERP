<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use RuntimeException;

final class TenantSubscriptionDataException extends RuntimeException
{
    public static function invalidDateTime(string $field, mixed $value): self
    {
        $display = is_scalar($value) ? trim((string) $value) : get_debug_type($value);

        return new self("Tenant subscription field [{$field}] contains an invalid date-time value [{$display}].");
    }
}
