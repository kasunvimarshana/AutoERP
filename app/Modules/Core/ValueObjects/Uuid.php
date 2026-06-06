<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\InvalidValueObjectException;

final class Uuid extends ValueObject
{
    private const UUID_PATTERN =
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function __construct(private readonly string $value)
    {
        if (preg_match(self::UUID_PATTERN, trim($value)) !== 1) {
            throw InvalidValueObjectException::because('Invalid UUID value provided.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param  array<string, mixed>  $data
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
        return ['value' => $this->value];
    }

    /**
     * @return array<int, scalar|null>
     */
    protected function primitives(): array
    {
        return [$this->value];
    }
}
