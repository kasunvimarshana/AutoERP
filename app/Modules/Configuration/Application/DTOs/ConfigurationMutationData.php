<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class ConfigurationMutationData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $source = null,
        public ?string $description = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['key'] ?? ''),
            $payload['value'] ?? null,
            isset($payload['source']) ? (string) $payload['source'] : null,
            isset($payload['description']) ? (string) $payload['description'] : null,
        );
    }
}
