<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\Data\ConfigurationDefinition;

interface ConfigurationDefinitionRegistryInterface
{
    public function get(string $key): ConfigurationDefinition;
    /** @return list<ConfigurationDefinition> */
    public function all(): array;
}
