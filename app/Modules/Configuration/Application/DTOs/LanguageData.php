<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

final readonly class LanguageData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $code,
        public string $name,
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
            'metadata' => $this->metadata,
        ];
    }
}
