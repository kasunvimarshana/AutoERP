<?php

declare(strict_types=1);

namespace Modules\Plugin\Application\DTOs;

/**
 * Data Transfer Object for installing a plugin manifest.
 */
final class InstallPluginDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $alias,
        public readonly ?string $description,
        public readonly string $version,
        public readonly array $keywords,
        public readonly array $requires,
        public readonly array $manifestData,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            alias: (string) $data['alias'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            version: (string) $data['version'],
            keywords: isset($data['keywords']) && is_array($data['keywords']) ? $data['keywords'] : [],
            requires: isset($data['requires']) && is_array($data['requires']) ? $data['requires'] : [],
            manifestData: isset($data['manifest_data']) && is_array($data['manifest_data']) ? $data['manifest_data'] : [],
        );
    }
}
