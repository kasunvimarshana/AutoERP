<?php

declare(strict_types=1);

namespace App\Domain\Catalog\ValueObjects;

final class ProductName
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Product name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('Product name cannot exceed 255 characters.');
        }

        $this->value = $trimmed;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string  { return $this->value; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string { return $this->value; }
}
