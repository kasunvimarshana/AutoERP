<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class TenantIsolationKey extends ValueObject
{
    public function __construct(private readonly string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Tenant isolation key cannot be empty.');
        }

        if (preg_match('/^[a-zA-Z0-9._:-]+$/', $trimmed) !== 1) {
            throw new InvalidArgumentException('Tenant isolation key contains invalid characters.');
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
