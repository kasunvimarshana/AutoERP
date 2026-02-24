<?php
namespace Modules\Shared\Domain\ValueObjects;
use InvalidArgumentException;
final class UserId
{
    public function __construct(private readonly string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('UserId cannot be empty.');
        }
    }
    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
