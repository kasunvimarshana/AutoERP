<?php

namespace Modules\Tenant\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class CacheConfig extends ValueObject
{
    private string $driver;
    private ?array $options;

    public function __construct(string $driver, ?array $options = null)
    {
        $this->driver = $driver;
        $this->options = $options;
    }

    public function getDriver(): string { return $this->driver; }
    public function getOptions(): ?array { return $this->options; }

    public function toArray(): array
    {
        return [
            'driver'  => $this->driver,
            'options' => $this->options,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['driver'] ?? 'file',
            $data['options'] ?? null
        );
    }
}
