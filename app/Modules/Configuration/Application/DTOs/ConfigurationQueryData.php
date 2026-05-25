<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class ConfigurationQueryData
{
    public function __construct(
        public ?string $prefix,
        public ?string $source,
        public int $page,
        public int $perPage,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['prefix']) ? (string) $payload['prefix'] : null,
            isset($payload['source']) ? (string) $payload['source'] : null,
            (int) ($payload['page'] ?? 0),
            (int) ($payload['per_page'] ?? $payload['perPage'] ?? 0),
        );
    }
}
