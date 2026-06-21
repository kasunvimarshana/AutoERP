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
        public string $valueType,
        public array $allowedScopes,
        public mixed $defaultValue,
        public bool $nullable,
        public bool $sensitive,
        public bool $runtimeMutable,
        public array $options = [],
        public ?float $minimum = null,
        public ?float $maximum = null,
        public ?string $lookup = null,
    ) {}
}
