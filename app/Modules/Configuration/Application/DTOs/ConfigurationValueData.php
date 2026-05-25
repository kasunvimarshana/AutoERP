<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class ConfigurationValueData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $source,
        public ?string $description = null,
        public ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'source' => $this->source,
            'description' => $this->description,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['key'] ?? ''),
            $payload['value'] ?? null,
            (string) ($payload['source'] ?? ''),
            isset($payload['description']) ? (string) $payload['description'] : null,
            isset($payload['updated_at']) ? (string) $payload['updated_at'] : null,
        );
    }
}
