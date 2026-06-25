<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

final readonly class ConfigurationDefinition
{
    /** @param list<string> $allowedScopes @param list<string|int|float|bool> $options */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $owner,
        public int $version,
        public string $valueType,
        public array $allowedScopes,
        public mixed $defaultValue,
        public bool $nullable,
        public bool $sensitive,
        public bool $runtimeMutable,
        public array $options = [],
        public ?string $minimum = null,
        public ?string $maximum = null,
        public ?string $lookup = null,
    ) {}
}
