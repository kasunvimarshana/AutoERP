<?php

namespace App\Modules\ModuleGenerator\Core;

use App\Modules\ModuleGenerator\Registry\GeneratorRegistry;

class ModuleGeneratorService
{
    public function __construct(protected GeneratorRegistry $registry) {}

    public function generate(array $context): void
    {
        $generators = ['domain', 'application', 'infrastructure', 'presentation'];

        foreach ($generators as $key) {
            $this->registry->get($key)->generate($context);
        }
    }
}
