<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

/**
 * Data Transfer Object for creating a Unit of Measure.
 */
final class CreateUomDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $symbol,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'],
            symbol:   $data['symbol'],
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'symbol'    => $this->symbol,
            'is_active' => $this->isActive,
        ];
    }
}
