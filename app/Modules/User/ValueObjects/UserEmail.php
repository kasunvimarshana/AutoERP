<?php

declare(strict_types=1);

namespace Modules\User\ValueObjects;

use InvalidArgumentException;

final readonly class UserEmail
{
    public function __construct(public string $value)
    {
        $normalized = strtolower(trim($this->value));
        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }
    }

    public function normalized(): string
    {
        return strtolower(trim($this->value));
    }
}
