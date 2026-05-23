<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class TimezoneData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $name,
        public string $offset,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            offset: (string) $data['offset'],
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'offset' => $this->offset,
            'metadata' => $this->metadata,
        ];
    }
}
