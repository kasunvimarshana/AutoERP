<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\Data\ConfigurationDefinition;

interface ConfigurationDefinitionRegistryInterface
{
    /** @param array<string, array<string, mixed>> $definitions */
    public function register(string $owner, array $definitions): void;

    public function get(string $key): ConfigurationDefinition;

    /** @return list<ConfigurationDefinition> */
    public function all(): array;
}
