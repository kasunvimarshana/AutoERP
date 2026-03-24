<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class FeatureFlags extends ValueObject
{
    private array $flags;

    public function __construct(array $flags)
    {
        $this->flags = $flags;
    }

    public function isEnabled(string $flag): bool
    {
        return $this->flags[$flag] ?? false;
    }

    public function toArray(): array
    {
        return $this->flags;
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
