<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class CurrencyData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $symbol = null,
        public int $decimalPlaces = 2,
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            name: (string) $data['name'],
            symbol: $data['symbol'] ?? null,
            decimalPlaces: (int) ($data['decimal_places'] ?? 2),
            isActive: (bool) ($data['is_active'] ?? true),
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'decimal_places' => $this->decimalPlaces,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}
