<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

use Modules\Core\Domain\Exceptions\InvalidValueObjectException;

final class OrganizationUnitId extends ValueObject
{
    public function __construct(private readonly int|string $value)
    {
        if (is_string($value) && trim($value) === '') {
            throw InvalidValueObjectException::because('OrganizationUnitId cannot be empty.');
        }
    }

    public function value(): int|string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $value = $data['value'] ?? '';
        if (! is_int($value) && ! is_string($value)) {
            $value = (string) $value;
        }

        return new self($value);
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return ['value' => (string) $this->value];
    }

    /**
     * @return array<int, scalar|null>
     */
    protected function primitives(): array
    {
        return [(string) $this->value];
    }
}
