<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Services;

use Illuminate\Contracts\Hashing\Hasher;
use InvalidArgumentException;
use Modules\Core\Application\Contracts\PasswordHasherInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly Hasher $hasher)
    {
    }

    public function hash(string $value): string
    {
        $candidate = trim($value);
        if ($candidate === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        return $this->hasher->make($value);
    }

    public function needsRehash(string $hashedValue): bool
    {
        $candidate = trim($hashedValue);
        if ($candidate === '') {
            throw new InvalidArgumentException('Hashed password cannot be empty.');
        }

        return $this->hasher->needsRehash($hashedValue);
    }
}
