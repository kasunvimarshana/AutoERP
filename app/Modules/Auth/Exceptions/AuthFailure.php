<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use RuntimeException;

final class AuthFailure extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
