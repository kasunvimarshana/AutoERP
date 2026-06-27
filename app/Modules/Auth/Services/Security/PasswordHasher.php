<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use Illuminate\Contracts\Hashing\Hasher;
use InvalidArgumentException;
use Modules\Auth\Contracts\PasswordHasherInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly Hasher $hasher) {}

    public function hash(string $value): string
    {
        $candidate = trim($value);
        if ($candidate === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        return $this->hasher->make($value);
    }

    public function verify(string $value, string $hashedValue): bool
    {
        $candidate = trim($value);
        if ($candidate === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        $hashedCandidate = trim($hashedValue);
        if ($hashedCandidate === '') {
            throw new InvalidArgumentException('Hashed password cannot be empty.');
        }

        return $this->hasher->check($value, $hashedValue);
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
