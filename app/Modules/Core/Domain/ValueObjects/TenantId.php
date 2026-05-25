<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

use Modules\Core\Domain\Exceptions\InvalidValueObjectException;

final class TenantId extends ValueObject
{
    public function __construct(private readonly int|string $value)
    {
        if ((string) $value === '') {
            throw InvalidValueObjectException::because('TenantId cannot be empty.');
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
        return new self((string) ($data['value'] ?? ''));
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
