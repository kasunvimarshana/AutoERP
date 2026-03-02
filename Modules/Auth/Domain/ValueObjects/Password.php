<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

final class Password
{
    private function __construct(
        private readonly string $hashedValue
    ) {}

    public static function fromPlainText(string $plain): self
    {
        if (strlen($plain) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long.');
        }

        $hashed = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($hashed === false) {
            throw new \RuntimeException('Failed to hash password.');
        }

        return new self($hashed);
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHashedValue(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plain): bool
    {
        return password_verify($plain, $this->hashedValue);
    }
}
