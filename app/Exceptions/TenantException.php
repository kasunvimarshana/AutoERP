<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class TenantException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('Tenant not found.', 404);
    }

    public static function inactive(): self
    {
        return new self('Tenant is inactive or suspended.', 403);
    }
}
